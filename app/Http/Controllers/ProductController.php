<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Collection;
use App\Models\InventoryReservation;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductImage;
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
            'has_side_stones' => ['sometimes', 'boolean'],
            'central_stone' => ['nullable', 'array'],
            'central_stone.stone_id' => [
                'nullable',
                'integer',
                Rule::exists('stones', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'central_stone.shape' => ['nullable', 'string', 'max:255'],
            'central_stone.carats' => ['nullable', 'numeric', 'min:0'],
            'central_stone.origin' => ['nullable', 'string', 'max:255'],
            'central_stone.certification' => ['nullable', 'string', 'max:255'],
            'central_stone.color' => ['nullable', 'string', 'max:255'],
            'central_stone.clarity' => ['nullable', 'string', 'max:255'],
            'central_stone.cut' => ['nullable', 'string', 'max:255'],
            'side_stone' => ['nullable', 'array'],
            'side_stone.stone_id' => [
                Rule::requiredIf($request->boolean('has_side_stones')),
                'nullable',
                'integer',
                Rule::exists('stones', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'side_stone.shape' => ['nullable', 'string', 'max:255'],
            'side_stone.carats' => ['nullable', 'numeric', 'min:0'],
            'side_stone.quantity' => [
                Rule::requiredIf($request->boolean('has_side_stones')),
                'nullable',
                'integer',
                'min:1',
            ],
            'side_stone.origin' => ['nullable', 'string', 'max:255'],
            'side_stone.certification' => ['nullable', 'string', 'max:255'],
            'side_stone.color' => ['nullable', 'string', 'max:255'],
            'side_stone.clarity' => ['nullable', 'string', 'max:255'],
            'side_stone.cut' => ['nullable', 'string', 'max:255'],
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
        $centralStone = $validated['central_stone'] ?? [];
        $sideStone = $validated['side_stone'] ?? [];

        $this->syncStonePosition($product, 'central', $centralStone, 1);

        $hasSideStones = filter_var($validated['has_side_stones'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($hasSideStones && filled($sideStone['stone_id'] ?? null)) {
            $this->syncStonePosition($product, 'lateral', $sideStone, (int) $sideStone['quantity']);

            return;
        }

        $product->productStones()
            ->where('position', 'lateral')
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $stoneData
     */
    private function syncStonePosition(Product $product, string $position, array $stoneData, int $quantity): void
    {
        if (! filled($stoneData['stone_id'] ?? null)) {
            $product->productStones()
                ->where('position', $position)
                ->delete();

            return;
        }

        $productStone = $product->productStones()
            ->where('position', $position)
            ->first();
        $attributes = [
            'stone_id' => $stoneData['stone_id'],
            'position' => $position,
            'shape' => $stoneData['shape'] ?? null,
            'carats' => $stoneData['carats'] ?? null,
            'quantity' => $quantity,
            'origin' => $stoneData['origin'] ?? null,
            'certification' => $stoneData['certification'] ?? null,
            'color' => $stoneData['color'] ?? null,
            'clarity' => $stoneData['clarity'] ?? null,
            'cut' => $stoneData['cut'] ?? null,
        ];

        if ($productStone) {
            $productStone->update($attributes);
            $product->productStones()
                ->where('position', $position)
                ->whereKeyNot($productStone->getKey())
                ->delete();

            return;
        }

        $product->productStones()->create($attributes);
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
