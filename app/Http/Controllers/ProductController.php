<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\InventoryReservation;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductStone;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\Stone;
use App\Support\CatalogPrice;
use App\Support\InventoryReservationStatus;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $products = Product::query()
            ->with([
                'category:id,name',
                'collection:id,name',
                'material:id,name',
                'baseSize:id,name',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_cover')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'variants' => fn ($query) => $query
                    ->withTrashed()
                    ->with('inventoryStock:id,product_variant_id,current_stock'),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('base_sku', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($query) use ($request): void {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->filled('collection_id'), function ($query) use ($request): void {
                $query->where('collection_id', $request->integer('collection_id'));
            })
            ->when(in_array($request->query('status'), ['draft', 'active', 'inactive'], true), function ($query) use ($request): void {
                $query->where('status', $request->query('status'));
            })
            ->orderByDesc('created_at')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $this->annotatePhysicalStock($products->getCollection());

        return view('products.index', [
            'products' => $products,
            'categories' => $this->activeCategories(),
            'collections' => $this->activeCollections(),
        ]);
    }

    public function deleted(): View
    {
        $products = Product::onlyTrashed()
            ->with([
                'category' => fn ($query) => $query->withTrashed()->select(['id', 'name']),
                'images' => fn ($query) => $query
                    ->orderByDesc('is_cover')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'variants' => fn ($query) => $query
                    ->withTrashed()
                    ->with('inventoryStock:id,product_variant_id,current_stock'),
            ])
            ->latest('deleted_at')
            ->latest('id')
            ->paginate(10);

        $this->annotatePhysicalStock($products->getCollection());

        return view('products.deleted', [
            'products' => $products,
        ]);
    }

    public function suggestCode(Request $request): JsonResponse
    {
        $name = trim((string) $request->query('name', ''));
        $categoryId = $request->integer('category_id');

        if ($name === '' || $categoryId === 0) {
            return response()->json(['code' => null]);
        }

        return response()->json([
            'code' => $this->uniqueCompactCode($name, $categoryId),
        ]);
    }

    public function create(Request $request): View
    {
        $product = new Product([
            'status' => 'draft',
            'featured' => false,
            'is_new' => false,
            'bestseller' => false,
        ]);

        return view('products.create', [
            'product' => $product,
            'codeWasGenerated' => true,
            ...$this->formOptions(
                $request->old('category_id', $request->integer('category_id') ?: null),
                $request->old('subcategory_id', $request->integer('subcategory_id') ?: null),
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedProductData($request);
        $validated['code'] = $this->resolveProductCode($validated);
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        DB::transaction(function () use ($request, $validated): void {
            $product = Product::create($this->normalizedProductData($validated));
            $this->createBaseVariant($product);
            $this->syncProductStones($product, $validated);
            $this->syncProductImages($product, $request);
        });

        return redirect()
            ->route('products.index')
            ->with('status', 'Producto creado correctamente.');
    }

    public function edit(Product $product): View
    {
        $product->load([
            'category' => fn ($query) => $query
                ->withTrashed()
                ->with([
                    'parent' => fn ($parentQuery) => $parentQuery->withTrashed(),
                ]),
            'productStones.stone',
            'productStones' => fn ($query) => $query
                ->orderBy('position')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'images' => fn ($query) => $query
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $category = $product->category;
        $categoryRelationInactive = $category === null
            || $category->trashed()
            || $category->parent?->trashed();

        return view('products.edit', [
            'product' => $product,
            'categoryRelationInactive' => $categoryRelationInactive,
            'codeWasGenerated' => $this->isLikelyGeneratedCode($product),
            ...$this->formOptions(
                $category?->parent_id ?: $product->category_id,
                $category?->parent_id ? $product->category_id : null,
            ),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validatedProductData($request, $product);
        $validated['code'] = $this->resolveProductCode($validated, $product);

        if ($validated['name'] !== $product->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $product);
        }

        DB::transaction(function () use ($product, $request, $validated): void {
            $product->update($this->normalizedProductData($validated, $product));

            if ($product->wasChanged('status')) {
                $this->synchronizeBaseVariantStatus($product);
            }

            $this->syncProductStones($product, $validated);
            $this->syncProductImages($product, $request);
        });

        return redirect()
            ->route('products.index')
            ->with('status', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $physicalStock = 0;

        DB::transaction(function () use ($product, &$physicalStock): void {
            $stocks = InventoryStock::query()
                ->whereHas('productVariant', fn ($query) => $query
                    ->withTrashed()
                    ->where('product_id', $product->id))
                ->lockForUpdate()
                ->get(['id', 'current_stock']);

            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($product->id);

            abort_unless(! $lockedProduct->trashed(), 404);

            $hasActiveReservations = InventoryReservation::query()
                ->whereIn('inventory_stock_id', $stocks->pluck('id'))
                ->where('status', InventoryReservationStatus::ACTIVE)
                ->exists();

            if ($hasActiveReservations) {
                throw ValidationException::withMessages([
                    'product' => 'No puedes retirar este producto mientras tenga reservas activas.',
                ]);
            }

            $physicalStock = (int) $stocks->sum('current_stock');
            $lockedProduct->delete();
        });

        return redirect()
            ->route('products.index')
            ->with(
                'status',
                $physicalStock > 0
                    ? "Producto retirado del catálogo. Se conservaron {$physicalStock} existencias físicas."
                    : 'Producto eliminado correctamente.',
            );
    }

    public function restore(Product $product): RedirectResponse
    {
        $product->restore();

        return redirect()
            ->route('products.deleted')
            ->with('status', 'Producto restaurado correctamente.');
    }

    public function reorderImages(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['integer', 'distinct'],
        ]);
        $imageIds = array_map('intval', $validated['images']);
        $productImageIds = $product->images()
            ->whereIn('id', $imageIds)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $currentImageIds = $product->images()
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();
        $submittedImageIds = collect($imageIds)->sort()->values()->all();

        if ($currentImageIds !== $submittedImageIds || count($imageIds) !== count($productImageIds)) {
            throw ValidationException::withMessages([
                'images' => 'Debes enviar todas las imágenes del producto, sin repetirlas.',
            ]);
        }

        DB::transaction(function () use ($product, $imageIds): void {
            foreach ($imageIds as $sortOrder => $imageId) {
                $product->images()
                    ->whereKey($imageId)
                    ->update(['sort_order' => $sortOrder]);
            }
        });

        return response()->json([
            'message' => 'Orden de imágenes actualizado correctamente.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProductData(Request $request, ?Product $product = null): array
    {
        $input = $request->all();
        $usesLegacyStoneInput = ! array_key_exists('stone_groups', $input);

        if ($usesLegacyStoneInput) {
            $input['stone_groups'] = $this->legacyStoneGroups($input, $product);
        }

        unset($input['central_stone'], $input['side_stones'], $input['side_stone']);
        $input['stone_groups'] ??= [];
        $salePrice = $input['sale_price'] ?? null;

        if ($salePrice === null
            || $salePrice === ''
            || (is_numeric($salePrice) && CatalogPrice::normalizeOptional((string) $salePrice) === null)) {
            $input['sale_price'] = null;
        }

        $validator = validator(
            $input,
            $this->rules($request, $product),
            $this->validationMessages(),
        );
        $validator->after(function ($validator) use ($request): void {
            if ($validator->errors()->hasAny(['category_id', 'base_size_id'])) {
                return;
            }

            $category = Category::find($request->integer('category_id'));
            $subcategory = $request->integer('subcategory_id')
                ? Category::find($request->integer('subcategory_id'))
                : null;
            $baseSizeId = $request->integer('base_size_id');

            if ($subcategory !== null && $subcategory->parent_id !== $category?->id) {
                $validator->errors()->add(
                    'subcategory_id',
                    'La subcategoría no pertenece a la categoría seleccionada.',
                );

                return;
            }

            $effectiveCategory = $subcategory ?? $category;

            if (
                $effectiveCategory !== null
                && $baseSizeId !== 0
                && ! $this->availableSizesForCategory($effectiveCategory)->contains('id', $baseSizeId)
            ) {
                $validator->errors()->add(
                    'base_size_id',
                    'La talla base no está disponible para la categoría seleccionada.',
                );
            }
        });
        $validator->after(function ($validator) use ($request, $product): void {
            if ($validator->errors()->has('code')) {
                return;
            }

            $code = trim((string) $request->input('code', ''));

            if ($code === '' || $request->boolean('code_generated')) {
                return;
            }

            $codeExists = Product::withTrashed()
                ->where(function ($query) use ($code): void {
                    $query->where('code', $code)->orWhere('base_sku', $code);
                })
                ->when($product, fn ($query) => $query->whereKeyNot($product->getKey()))
                ->exists();

            if ($codeExists) {
                $validator->errors()->add(
                    'code',
                    'El código ya está en uso.',
                );
            }
        });
        $validator->after(function ($validator) use ($input): void {
            if ($validator->errors()->hasAny(['price', 'sale_price'])) {
                return;
            }

            $salePrice = $input['sale_price'] ?? null;

            if ($salePrice !== null
                && ! CatalogPrice::isValidSalePrice(
                    (string) $salePrice,
                    isset($input['price']) ? (string) $input['price'] : null,
                )) {
                $validator->errors()->add(
                    'sale_price',
                    'El precio de oferta debe ser mayor que cero y menor que el precio normal.',
                );
            }
        });
        $validator->after(function ($validator) use ($input, $product): void {
            if ($product === null || ! is_array($input['stone_groups'] ?? null)) {
                return;
            }

            foreach ($input['stone_groups'] as $index => $stoneGroup) {
                $productStoneId = $stoneGroup['id'] ?? null;

                if (! filled($productStoneId)) {
                    continue;
                }

                $belongsToProduct = $product->productStones()
                    ->whereKey((int) $productStoneId)
                    ->exists();

                if (! $belongsToProduct) {
                    $validator->errors()->add(
                        "stone_groups.{$index}.id",
                        'El grupo de piedras no pertenece a este producto.',
                    );
                }
            }
        });
        $validator->after(function ($validator) use ($input): void {
            if (! is_array($input['stone_groups'] ?? null)) {
                return;
            }

            $mainGroups = 0;

            foreach ($input['stone_groups'] as $index => $stoneGroup) {
                $role = $stoneGroup['role'] ?? null;
                $distribution = $stoneGroup['distribution'] ?? null;
                $stonesPerSide = $stoneGroup['stones_per_side'] ?? null;
                $quantity = $stoneGroup['quantity'] ?? null;

                if ($role === ProductStone::ROLE_MAIN) {
                    $mainGroups++;

                    if ($distribution !== ProductStone::DISTRIBUTION_SINGLE) {
                        $validator->errors()->add(
                            "stone_groups.{$index}.distribution",
                            'La piedra principal debe tener distribución única.',
                        );
                    }
                }

                if ($distribution === ProductStone::DISTRIBUTION_SYMMETRIC
                    && ! filled($stonesPerSide)) {
                    $validator->errors()->add(
                        "stone_groups.{$index}.stones_per_side",
                        'Indica la cantidad por lado para una distribución simétrica.',
                    );
                }

                if ($distribution !== ProductStone::DISTRIBUTION_SYMMETRIC
                    && filled($stonesPerSide)) {
                    $validator->errors()->add(
                        "stone_groups.{$index}.stones_per_side",
                        'La cantidad por lado solo aplica a distribuciones simétricas.',
                    );
                }

                if ($distribution === ProductStone::DISTRIBUTION_DISTRIBUTED
                    && ! filled($quantity)) {
                    $validator->errors()->add(
                        "stone_groups.{$index}.quantity",
                        'Indica la cantidad total de piedras distribuidas.',
                    );
                }
            }

            if ($mainGroups > 1) {
                $validator->errors()->add(
                    'stone_groups',
                    'Solo puede existir un grupo de piedras principal por producto.',
                );
            }
        });
        $validator->after(function ($validator) use ($usesLegacyStoneInput): void {
            if (! $usesLegacyStoneInput) {
                return;
            }

            foreach ($validator->errors()->get('stone_groups.*.stone_id') as $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add('central_stone.stone_id', $message);
                }
            }

            foreach ($validator->errors()->get('stone_groups.*.stones_per_side') as $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add('side_stones.0.stones_per_side', $message);
                }
            }

            foreach ($validator->errors()->get('stone_groups.*.id') as $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add('side_stones.0.id', $message);
                }
            }
        });

        $validated = $validator->validate();

        if (filled($validated['subcategory_id'] ?? null)) {
            $validated['category_id'] = $validated['subcategory_id'];
        }

        unset($validated['subcategory_id']);

        return $validated;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(Request $request, ?Product $product = null): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('parent_id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'subcategory_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNotNull('parent_id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'collection_id' => [
                'nullable',
                'integer',
                Rule::exists('collections', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'material_id' => [
                'nullable',
                'integer',
                Rule::exists('materials', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'base_size_id' => [
                'nullable',
                'integer',
                Rule::exists('sizes', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:255',
                ...($request->boolean('code_generated')
                    ? []
                    : [Rule::unique('products', 'code')->ignore($product)]),
            ],
            'code_generated' => ['sometimes', 'boolean'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'karat' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'metal_color' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'gt:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'featured' => ['sometimes', 'boolean'],
            'is_new' => ['sometimes', 'boolean'],
            'bestseller' => ['sometimes', 'boolean'],
            'stone_groups' => ['nullable', 'array', 'max:50'],
            'stone_groups.*' => ['required', 'array'],
            'stone_groups.*.id' => ['nullable', 'integer', 'distinct'],
            'stone_groups.*.role' => ['required', Rule::in(ProductStone::roles())],
            'stone_groups.*.distribution' => ['required', Rule::in(ProductStone::distributions())],
            'stone_groups.*.shape' => ['nullable', 'string', 'max:255'],
            'stone_groups.*.carats' => ['nullable', 'numeric', 'min:0'],
            'stone_groups.*.stones_per_side' => ['nullable', 'integer', 'min:1', 'max:50'],
            'stone_groups.*.quantity' => ['nullable', 'integer', 'min:1'],
            'stone_groups.*.origin' => ['nullable', 'string', 'max:255'],
            'stone_groups.*.certification' => ['nullable', 'string', 'max:255'],
            'stone_groups.*.color' => ['nullable', 'string', 'max:255'],
            'stone_groups.*.clarity' => ['nullable', 'string', 'max:255'],
            'stone_groups.*.cut' => ['nullable', 'string', 'max:255'],
            'stone_groups.*.stone_id' => [
                'required',
                'integer',
                Rule::exists('stones', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_alt' => ['nullable', 'array'],
            'image_alt.*' => ['nullable', 'string', 'max:255'],
            'new_image_alts' => ['nullable', 'array'],
            'new_image_alts.*' => ['nullable', 'string', 'max:255'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer'],
            'cover_image_id' => ['nullable', 'integer'],
            'cover_new_index' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'code.unique' => 'El código ya está en uso.',
            'images.*.uploaded' => 'No se pudo subir la imagen seleccionada. Verifica su tamaño e inténtalo nuevamente.',
            'images.*.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'images.*.mimes' => 'La imagen debe estar en formato JPG, JPEG, PNG o WEBP.',
            'images.*.max' => 'La imagen supera el tamaño máximo permitido de 2 MB.',
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, array<string, mixed>>
     */
    private function legacyStoneGroups(array $input, ?Product $product = null): array
    {
        $groups = [];
        $centralStone = $input['central_stone'] ?? [];
        $centralProductStone = $product?->productStones()->where('position', 'central')->first();

        if (is_array($centralStone) && filled($centralStone['stone_id'] ?? null)) {
            $groups[] = [
                ...$centralStone,
                'id' => $centralStone['id'] ?? $centralProductStone?->id,
                'role' => ProductStone::ROLE_MAIN,
                'distribution' => ProductStone::DISTRIBUTION_SINGLE,
                'quantity' => 1,
                'stones_per_side' => null,
            ];
        }

        $sideStones = $input['side_stones'] ?? $input['side_stone'] ?? [];
        $sideStones = is_array($sideStones) && array_key_exists('stone_id', $sideStones)
            ? [$sideStones]
            : $sideStones;

        foreach (array_values(is_array($sideStones) ? $sideStones : []) as $sideStone) {
            $quantity = filled($sideStone['quantity'] ?? null)
                ? (int) $sideStone['quantity']
                : null;
            $stonesPerSide = $sideStone['stones_per_side'] ?? null;

            if ($stonesPerSide === null && $quantity !== null && $quantity >= 2 && $quantity % 2 === 0) {
                $stonesPerSide = intdiv($quantity, 2);
            }

            $groups[] = [
                ...$sideStone,
                'role' => ProductStone::ROLE_ACCENT,
                'distribution' => ProductStone::DISTRIBUTION_SYMMETRIC,
                'stones_per_side' => $stonesPerSide,
            ];
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizedProductData(array $validated, ?Product $product = null): array
    {
        $validated['base_sku'] = $product !== null && $validated['code'] === $product->code
            ? $product->base_sku
            : $validated['code'];
        $validated['model'] = $validated['model'] ?? '';

        foreach (['featured', 'is_new', 'bestseller'] as $visibilityField) {
            $validated[$visibilityField] = $product !== null && ! array_key_exists($visibilityField, $validated)
                ? $product->{$visibilityField}
                : filter_var($validated[$visibilityField] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return $validated;
    }

    private function createBaseVariant(Product $product): void
    {
        $variant = $product->variants()->create([
            'sku' => $this->baseVariantSku($product),
            'size_id' => null,
            'material_id' => null,
            'stone_id' => null,
            'karat' => null,
            'metal_color' => null,
            'cost' => null,
            'price' => null,
            'sale_price' => null,
            'status' => $product->status,
        ]);

        $variant->inventoryStock()->create([
            'current_stock' => 0,
            'minimum_stock' => 0,
        ]);
    }

    private function baseVariantSku(Product $product): string
    {
        $baseSku = (string) $product->base_sku;
        $candidate = $baseSku;
        $suffix = 2;

        while (ProductVariant::withTrashed()->where('sku', $candidate)->exists()) {
            $candidate = $baseSku.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function synchronizeBaseVariantStatus(Product $product): void
    {
        $baseVariant = $product->variants()
            ->where('sku', $product->base_sku)
            ->first();

        if ($baseVariant === null || ! $this->isAutomaticBaseVariant($baseVariant)) {
            return;
        }

        $baseVariant->update(['status' => $product->status]);
    }

    private function isAutomaticBaseVariant(ProductVariant $variant): bool
    {
        return $variant->size_id === null
            && $variant->material_id === null
            && $variant->stone_id === null
            && $variant->karat === null
            && $variant->metal_color === null
            && $variant->cost === null
            && $variant->price === null
            && $variant->sale_price === null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveProductCode(array $validated, ?Product $product = null): string
    {
        $code = trim((string) ($validated['code'] ?? ''));
        $isGenerated = (bool) ($validated['code_generated'] ?? false);

        if (! $isGenerated && $code !== '') {
            return $code;
        }

        if ($product !== null && ! $isGenerated) {
            return $product->code;
        }

        return $this->uniqueCompactCode(
            (string) $validated['name'],
            (int) $validated['category_id'],
            $product,
        );
    }

    private function uniqueCompactCode(string $name, int $categoryId, ?Product $product = null): string
    {
        $category = Category::with('parent')->find($categoryId);
        $mainCategory = $category?->parent ?? $category;
        $categoryPrefix = $this->compactCodePart($mainCategory?->name ?: 'PRO');
        $nameWords = preg_split('/\s+/', trim(Str::ascii($name))) ?: [];

        if ($nameWords !== [] && str_starts_with(Str::upper($nameWords[0]), $categoryPrefix)) {
            array_shift($nameWords);
        }

        $namePrefix = $this->compactCodePart($nameWords[0] ?? $name);
        $namePrefix = $namePrefix !== '' ? $namePrefix : 'PRO';
        $baseCode = "{$categoryPrefix}-{$namePrefix}";
        $sequence = 1;

        do {
            $code = $baseCode.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $exists = Product::withTrashed()
                ->where(function ($query) use ($code): void {
                    $query->where('code', $code)->orWhere('base_sku', $code);
                })
                ->when($product, fn ($query) => $query->whereKeyNot($product->getKey()))
                ->exists();
            $sequence++;
        } while ($exists);

        return $code;
    }

    private function compactCodePart(string $value): string
    {
        return Str::upper(substr((string) preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($value)), 0, 3));
    }

    private function isLikelyGeneratedCode(Product $product): bool
    {
        if (! filled($product->code) || $product->category === null) {
            return false;
        }

        $mainCategory = $product->category->parent ?? $product->category;
        $categoryPrefix = $this->compactCodePart($mainCategory->name);
        $nameWords = preg_split('/\s+/', trim(Str::ascii($product->name))) ?: [];

        if ($nameWords !== [] && str_starts_with(Str::upper($nameWords[0]), $categoryPrefix)) {
            array_shift($nameWords);
        }

        $namePrefix = $this->compactCodePart($nameWords[0] ?? $product->name) ?: 'PRO';
        $generatedPattern = '/^'.preg_quote($categoryPrefix.'-'.$namePrefix, '/').'\-\d{3}$/';

        return preg_match($generatedPattern, $product->code) === 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?int $categoryId, ?int $subcategoryId = null): array
    {
        $mainCategories = $this->activeMainCategories();
        $categorySizes = $this->categorySizes($mainCategories);
        $effectiveCategoryId = $subcategoryId ?: $categoryId;
        $effectiveCategory = $effectiveCategoryId
            ? Category::find($effectiveCategoryId)
            : null;

        return [
            'categories' => $mainCategories,
            'collections' => $this->activeCollections(),
            'materials' => $this->activeMaterials(),
            'stones' => $this->activeStones(),
            'stoneRoles' => [
                ProductStone::ROLE_MAIN => 'Principal',
                ProductStone::ROLE_ACCENT => 'Acento',
                ProductStone::ROLE_HALO => 'Halo',
                ProductStone::ROLE_PAVE => 'Pavé',
                ProductStone::ROLE_OTHER => 'Otro',
            ],
            'stoneDistributions' => [
                ProductStone::DISTRIBUTION_SINGLE => 'Única',
                ProductStone::DISTRIBUTION_SYMMETRIC => 'Simétrica',
                ProductStone::DISTRIBUTION_DISTRIBUTED => 'Distribuida',
            ],
            'categorySizes' => $categorySizes,
            'selectedCategoryId' => $categoryId,
            'selectedSubcategoryId' => $subcategoryId,
            'applicableSizes' => $effectiveCategory
                ? $this->availableSizesForCategory($effectiveCategory)
                : collect(),
        ];
    }

    /**
     * @return EloquentCollection<int, Category>
     */
    private function activeMainCategories(): EloquentCollection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('status', true)
            ->with([
                'children' => fn ($query) => $query
                    ->where('status', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
                'sizes' => fn ($query) => $query
                    ->where('sizes.status', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    /**
     * @param  EloquentCollection<int, Category>  $categories
     * @return array<int, array<int, array{id: int, name: string}>>
     */
    private function categorySizes(EloquentCollection $categories): array
    {
        $categorySizes = [];

        foreach ($categories as $category) {
            $sizes = $category->sizes
                ->map(fn (Size $size): array => [
                    'id' => $size->id,
                    'name' => $size->name,
                ])
                ->values()
                ->all();

            $categorySizes[$category->id] = $sizes;

            foreach ($category->children as $child) {
                $categorySizes[$child->id] = $sizes;
            }
        }

        return $categorySizes;
    }

    /**
     * @return EloquentCollection<int, Category>
     */
    private function activeCategories(): EloquentCollection
    {
        return Category::query()
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    /**
     * @return EloquentCollection<int, Collection>
     */
    private function activeCollections(): EloquentCollection
    {
        return Collection::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function annotatePhysicalStock(EloquentCollection $products): void
    {
        $products->each(function (Product $product): void {
            $physicalStock = $product->variants->sum(
                fn (ProductVariant $variant): int => (int) $variant->inventoryStock?->current_stock,
            );

            $product->setAttribute('physical_stock', $physicalStock);
        });
    }

    /**
     * @return EloquentCollection<int, Material>
     */
    private function activeMaterials(): EloquentCollection
    {
        return Material::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return EloquentCollection<int, Stone>
     */
    private function activeStones(): EloquentCollection
    {
        return Stone::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return EloquentCollection<int, Size>
     */
    private function availableSizesForCategory(Category $category): EloquentCollection
    {
        return $category->applicableSizes()
            ->filter(fn ($size): bool => $size->status)
            ->values();
    }

    private function uniqueSlug(string $name, ?Product $product = null): string
    {
        $baseSlug = Str::slug($name) ?: 'producto';
        $slug = $baseSlug;
        $suffix = 2;

        while (
            Product::withTrashed()
                ->where('slug', $slug)
                ->when($product, fn ($query) => $query->whereKeyNot($product->getKey()))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncProductStones(Product $product, array $validated): void
    {
        $existingProductStones = $product->productStones()
            ->get()
            ->keyBy('id');
        $submittedProductStoneIds = [];

        foreach ($validated['stone_groups'] ?? [] as $sortOrder => $stoneGroup) {
            $productStone = filled($stoneGroup['id'] ?? null)
                ? $existingProductStones->get((int) $stoneGroup['id'])
                : null;
            $attributes = $this->productStoneAttributes($stoneGroup, $sortOrder + 1);

            if ($productStone !== null) {
                $productStone->update($attributes);
                $submittedProductStoneIds[] = $productStone->getKey();

                continue;
            }

            $submittedProductStoneIds[] = $product->productStones()->create($attributes)->getKey();
        }

        $product->productStones()
            ->when($submittedProductStoneIds !== [], fn ($query) => $query->whereNotIn('id', $submittedProductStoneIds))
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $stoneData
     */
    private function productStoneAttributes(array $stoneData, int $sortOrder): array
    {
        $role = (string) $stoneData['role'];
        $distribution = (string) $stoneData['distribution'];
        $stonesPerSide = $distribution === ProductStone::DISTRIBUTION_SYMMETRIC
            ? (int) $stoneData['stones_per_side']
            : null;
        $position = match (true) {
            $role === ProductStone::ROLE_MAIN
                && $distribution === ProductStone::DISTRIBUTION_SINGLE => 'central',
            $role === ProductStone::ROLE_ACCENT
                && $distribution === ProductStone::DISTRIBUTION_SYMMETRIC => 'lateral',
            default => null,
        };
        $quantity = match ($distribution) {
            ProductStone::DISTRIBUTION_SYMMETRIC => $stonesPerSide * 2,
            ProductStone::DISTRIBUTION_DISTRIBUTED => (int) $stoneData['quantity'],
            default => 1,
        };

        return [
            'stone_id' => $stoneData['stone_id'],
            'position' => $position,
            'role' => $role,
            'distribution' => $distribution,
            'sort_order' => $sortOrder,
            'shape' => $stoneData['shape'] ?? null,
            'carats' => $stoneData['carats'] ?? null,
            'quantity' => $quantity,
            'stones_per_side' => $stonesPerSide,
            'origin' => $stoneData['origin'] ?? null,
            'certification' => $stoneData['certification'] ?? null,
            'color' => $stoneData['color'] ?? null,
            'clarity' => $stoneData['clarity'] ?? null,
            'cut' => $stoneData['cut'] ?? null,
        ];
    }

    private function syncProductImages(Product $product, Request $request): void
    {
        $existingImages = $product->images()->get();
        $currentCoverId = $existingImages->firstWhere('is_cover', true)?->id;
        $removeImageIds = collect($request->input('remove_image_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->all();

        foreach ($existingImages->whereIn('id', $removeImageIds) as $image) {
            if (is_file($image->publicFilePath())) {
                unlink($image->publicFilePath());
            }

            $image->delete();
        }

        $this->normalizeImageSortOrder($product);

        foreach ($request->input('image_alt', []) as $imageId => $alt) {
            $image = $existingImages->firstWhere('id', (int) $imageId);

            if ($image && ! in_array($image->id, $removeImageIds, true)) {
                $image->update(['alt' => $alt]);
            }
        }

        $lastSortOrder = (int) $product->images()->max('sort_order');
        $newImages = [];
        $directory = (string) config('aurea.uploads.products_path', public_path('uploads/products'));

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('No se pudo crear el directorio público de imágenes.');
        }

        $createdFilePaths = [];

        try {
            foreach ($request->file('images', []) as $index => $file) {
                $filename = 'prod-'.$product->getKey().'-'.Str::lower(Str::random(16)).'.'.strtolower($file->getClientOriginalExtension());
                $finalPath = $directory.DIRECTORY_SEPARATOR.$filename;
                $createdFilePaths[] = $finalPath;
                $movedFile = $file->move($directory, $filename);

                if (! $movedFile || ! is_file($finalPath)) {
                    throw new \RuntimeException("No se pudo mover la imagen a {$finalPath}.");
                }

                $alt = trim((string) $request->input("new_image_alts.{$index}"));

                $newImages[$index] = $product->images()->create([
                    'path' => $filename,
                    'alt' => $alt !== '' ? $alt : $product->name,
                    'sort_order' => ++$lastSortOrder,
                    'is_cover' => false,
                ]);
            }
        } catch (\Throwable $exception) {
            foreach ($createdFilePaths as $createdFilePath) {
                if (is_file($createdFilePath)) {
                    unlink($createdFilePath);
                }
            }

            throw $exception;
        }

        $imageOrder = collect(explode(',', (string) $request->input('image_order')))
            ->filter()
            ->values();

        if ($imageOrder->isNotEmpty()) {
            $imagesByToken = $product->images()
                ->get()
                ->mapWithKeys(fn (ProductImage $image): array => [(string) $image->id => $image]);

            foreach ($newImages as $index => $image) {
                $imagesByToken["new-{$index}"] = $image;
            }

            foreach ($imageOrder as $sortOrder => $token) {
                $imagesByToken->get($token)?->update(['sort_order' => $sortOrder]);
            }
        } else {
            $this->normalizeImageSortOrder($product);
        }

        $product->images()->update(['is_cover' => false]);

        $coverImageId = (int) $request->input('cover_image_id');

        if ($coverImageId > 0 && $product->images()->whereKey($coverImageId)->exists()) {
            $product->images()->whereKey($coverImageId)->update(['is_cover' => true]);

            return;
        }

        $coverNewIndex = $request->input('cover_new_index');

        if ($coverNewIndex !== null && isset($newImages[(int) $coverNewIndex])) {
            $newImages[(int) $coverNewIndex]->update(['is_cover' => true]);

            return;
        }

        if ($currentCoverId && $product->images()->whereKey($currentCoverId)->exists()) {
            $product->images()->whereKey($currentCoverId)->update(['is_cover' => true]);

            return;
        }

        $firstImage = $product->images()
            ->orderBy('sort_order')
            ->first();

        $firstImage?->update(['is_cover' => true]);
    }

    private function normalizeImageSortOrder(Product $product): void
    {
        $product->images()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (ProductImage $image, int $sortOrder): void {
                if ($image->sort_order !== $sortOrder) {
                    $image->update(['sort_order' => $sortOrder]);
                }
            });
    }
}
