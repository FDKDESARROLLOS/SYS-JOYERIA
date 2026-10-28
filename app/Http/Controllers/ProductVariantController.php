<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function index(Product $product): View
    {
        $product->load([
            'category.parent',
            'material:id,name',
            'baseSize:id,name',
            'productStones.stone:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_cover')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $productVariants = $product->variants()
            ->with([
                'size:id,name',
                'material:id,name',
                'stone:id,name',
                'inventoryStock:id,product_variant_id,current_stock,minimum_stock',
            ])
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('products.variants.index', [
            'product' => $this->productContext($product),
            'productModel' => $product,
            'productVariants' => $productVariants,
        ]);
    }

    public function deleted(Product $product): View
    {
        $product->load([
            'category.parent',
            'material' => fn ($query) => $query->withTrashed()->select(['id', 'name']),
            'baseSize' => fn ($query) => $query->withTrashed()->select(['id', 'name']),
            'productStones.stone' => fn ($query) => $query->withTrashed()->select(['id', 'name']),
            'images' => fn ($query) => $query
                ->orderByDesc('is_cover')
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $deletedVariants = $product->variants()
            ->onlyTrashed()
            ->with([
                'size' => fn ($query) => $query->withTrashed()->select(['id', 'name']),
                'material' => fn ($query) => $query->withTrashed()->select(['id', 'name']),
                'stone' => fn ($query) => $query->withTrashed()->select(['id', 'name']),
                'inventoryStock:id,product_variant_id,current_stock',
            ])
            ->latest('deleted_at')
            ->latest('id')
            ->paginate(10);

        return view('products.variants.deleted', [
            'product' => $this->productContext($product),
            'productModel' => $product,
            'productVariants' => $deletedVariants,
        ]);
    }

    public function create(Product $product): View
    {
        $product->load([
            'category.parent',
            'images',
            'baseSize',
            'material' => fn ($query) => $query->withTrashed(),
            'productStones',
        ]);

        return view('products.variants.create', [
            'product' => $this->productContext($product),
            'productModel' => $product,
            'variant' => new ProductVariant(['status' => 'active']),
            ...$this->formOptions($product),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validatedVariantData($request, $product);
        $validated['product_id'] = $product->id;
        $validated['sku'] = $this->uniqueSku($product, $validated);

        DB::transaction(function () use ($validated): void {
            $variant = ProductVariant::create($validated);
            $variant->inventoryStock()->create([
                'current_stock' => 0,
                'minimum_stock' => 0,
            ]);
        });

        return redirect()
            ->route('products.variants.index', $product)
            ->with('status', 'Variante creada correctamente.');
    }

    public function edit(Product $product, ProductVariant $variant): View
    {
        $this->ensureVariantBelongsToProduct($product, $variant);
        $product->load([
            'category.parent',
            'images',
            'baseSize',
            'material' => fn ($query) => $query->withTrashed(),
            'productStones',
        ]);

        return view('products.variants.edit', [
            'product' => $this->productContext($product),
            'productModel' => $product,
            'variant' => $variant->load(['size', 'material', 'stone']),
            ...$this->formOptions($product),
        ]);
    }

    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->ensureVariantBelongsToProduct($product, $variant);
        $validated = $this->validatedVariantData($request, $product, $variant);
        $validated['sku'] = $this->uniqueSku($product, $validated, $variant);

        $variant->update($validated);

        return redirect()
            ->route('products.variants.index', $product)
            ->with('status', 'Variante actualizada correctamente.');
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->ensureVariantBelongsToProduct($product, $variant);
        $currentStock = 0;

        DB::transaction(function () use ($variant, &$currentStock): void {
            $currentStock = (int) (InventoryStock::query()
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->value('current_stock') ?? 0);

            $hasActiveReservation = InventoryReservation::query()
                ->where('product_variant_id', $variant->id)
                ->where('status', InventoryReservationStatus::ACTIVE)
                ->exists();

            if ($hasActiveReservation) {
                throw ValidationException::withMessages([
                    'variant' => 'No puedes retirar esta variante mientras tenga una reserva activa.',
                ]);
            }

            $variant->delete();
        });

        return redirect()
            ->route('products.variants.index', $product)
            ->with(
                'status',
                $currentStock > 0
                    ? "Variante retirada del catálogo. Se conservaron {$currentStock} existencias físicas."
                    : 'Variante eliminada correctamente.',
            );
    }

    public function restore(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->ensureVariantBelongsToProduct($product, $variant);
        abort_unless($variant->trashed(), 404);

        $restoreError = DB::transaction(function () use ($product, $variant): ?string {
            if ($product->trashed()) {
                return 'parent';
            }

            $skuConflictExists = ProductVariant::query()
                ->where('sku', $variant->sku)
                ->whereKeyNot($variant->getKey())
                ->exists();

            if ($skuConflictExists) {
                return 'sku';
            }

            $combination = [
                'size_id' => $variant->size_id,
                'material_id' => $variant->material_id,
                'stone_id' => $variant->stone_id,
                'karat' => $variant->karat,
                'metal_color' => $variant->metal_color,
            ];

            if ($this->combinationExists($product, $combination, $variant)) {
                return 'combination';
            }

            $variant->restore();

            return null;
        });

        if ($restoreError === 'parent') {
            return redirect()
                ->route('products.index')
                ->with('error', 'No se puede restaurar la variante porque su producto está eliminado.');
        }

        if ($restoreError === 'sku') {
            return redirect()
                ->route('products.variants.deleted', $product)
                ->with('error', 'No se puede restaurar la variante porque su SKU ya está en uso.');
        }

        if ($restoreError === 'combination') {
            return redirect()
                ->route('products.variants.deleted', $product)
                ->with('error', 'No se puede restaurar esta variante porque ya existe una variante activa con las mismas características.');
        }

        return redirect()
            ->route('products.variants.index', $product)
            ->with('status', 'Variante restaurada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedVariantData(
        Request $request,
        Product $product,
        ?ProductVariant $variant = null,
    ): array {
        $input = $this->normalizeInheritedAttributes($request->all(), $product);
        $salePrice = $input['sale_price'] ?? null;

        if ($salePrice === null
            || $salePrice === ''
            || (is_numeric($salePrice) && CatalogPrice::normalizeOptional((string) $salePrice) === null)) {
            $input['sale_price'] = null;
        }

        $validator = validator($input, $this->rules(), $this->validationMessages());
        $validator->after(function (Validator $validator) use ($input, $product, $variant): void {
            if ($validator->errors()->hasAny(['size_id', 'material_id', 'stone_id', 'karat', 'metal_color'])) {
                return;
            }

            if (filled($input['size_id'] ?? null)) {
                $applicableSizeIds = $this->applicableSizes($product)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id);

                if (! $applicableSizeIds->contains((int) $input['size_id'])) {
                    $validator->errors()->add(
                        'size_id',
                        'La talla seleccionada no está disponible para la categoría del producto.',
                    );
                }
            }

            $combination = [
                'size_id' => filled($input['size_id'] ?? null) ? (int) $input['size_id'] : null,
                'material_id' => filled($input['material_id'] ?? null) ? (int) $input['material_id'] : null,
                'stone_id' => filled($input['stone_id'] ?? null) ? (int) $input['stone_id'] : null,
                'karat' => filled($input['karat'] ?? null) ? (int) $input['karat'] : null,
                'metal_color' => filled($input['metal_color'] ?? null)
                    ? trim((string) $input['metal_color'])
                    : null,
            ];

            if ($this->hasNoVariantAttributes($combination)) {
                $validator->errors()->add(
                    'material_id',
                    'Selecciona al menos un atributo que diferencie esta variante del producto base.',
                );

                return;
            }

            if ($this->combinationExists($product, $combination, $variant)) {
                $validator->errors()->add(
                    'size_id',
                    'Ya existe una variante con esta combinación de atributos.',
                );
            }
        });
        $validator->after(function (Validator $validator) use ($input, $product): void {
            if ($validator->errors()->hasAny(['price', 'sale_price'])) {
                return;
            }

            $salePrice = $input['sale_price'] ?? null;
            $normalPrice = $input['price'] ?? $product->price;

            if ($salePrice !== null
                && ! CatalogPrice::isValidSalePrice(
                    (string) $salePrice,
                    $normalPrice !== null ? (string) $normalPrice : null,
                )) {
                $validator->errors()->add(
                    'sale_price',
                    'El precio de oferta debe ser mayor que cero y menor que el precio normal efectivo.',
                );
            }
        });

        $validated = $validator->validate();

        foreach (['material_id', 'stone_id'] as $nullableId) {
            $validated[$nullableId] = filled($validated[$nullableId] ?? null)
                ? (int) $validated[$nullableId]
                : null;
        }

        $validated['size_id'] = filled($validated['size_id'] ?? null)
            ? (int) $validated['size_id']
            : null;

        $validated['karat'] = filled($validated['karat'] ?? null)
            ? (int) $validated['karat']
            : null;
        $validated['metal_color'] = filled($validated['metal_color'] ?? null)
            ? trim((string) $validated['metal_color'])
            : null;

        return $validated;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'size_id' => [
                'nullable',
                'integer',
                Rule::exists('sizes', 'id')
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
            'stone_id' => [
                'nullable',
                'integer',
                Rule::exists('stones', 'id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
            'karat' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'metal_color' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'gt:0'],
            'status' => ['required', Rule::in(['active', 'draft', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'size_id.exists' => 'La talla seleccionada no está disponible.',
            'material_id.exists' => 'El material seleccionado no está disponible.',
            'stone_id.exists' => 'La piedra seleccionada no está disponible.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Product $product): array
    {
        $product->loadMissing(['baseSize', 'productStones']);
        $baseStoneIds = $product->productStones->pluck('stone_id');

        $karatOptions = [
            10 => '10K',
            14 => '14K',
            18 => '18K',
        ];

        if ($product->karat !== null) {
            unset($karatOptions[$product->karat]);
        }

        $metalColorOptions = collect(['Amarillo', 'Blanco', 'Rosa'])
            ->reject(fn (string $color): bool => $color === $product->metal_color)
            ->values()
            ->all();

        return [
            'applicableSizes' => $this->applicableSizes($product),
            'materials' => Material::query()
                ->where('status', true)
                ->when($product->material_id !== null, fn ($query) => $query->whereKeyNot($product->material_id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'stones' => Stone::query()
                ->where('status', true)
                ->when($baseStoneIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $baseStoneIds))
                ->orderBy('name')
                ->get(['id', 'name']),
            'karatOptions' => $karatOptions,
            'metalColorOptions' => $metalColorOptions,
            'baseStoneIds' => $baseStoneIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeInheritedAttributes(array $input, Product $product): array
    {
        $product->loadMissing(['baseSize', 'productStones']);

        if (filled($input['material_id'] ?? null) && (int) $input['material_id'] === (int) $product->material_id) {
            $input['material_id'] = null;
        }

        if (filled($input['karat'] ?? null) && (int) $input['karat'] === (int) $product->karat) {
            $input['karat'] = null;
        }

        if (filled($input['metal_color'] ?? null)
            && trim((string) $input['metal_color']) === trim((string) $product->metal_color)) {
            $input['metal_color'] = null;
        }

        if (filled($input['size_id'] ?? null) && (int) $input['size_id'] === (int) $product->base_size_id) {
            $input['size_id'] = null;
        }

        if (filled($input['stone_id'] ?? null)
            && $product->productStones->pluck('stone_id')->contains((int) $input['stone_id'])) {
            $input['stone_id'] = null;
        }

        return $input;
    }

    /**
     * @return EloquentCollection<int, Size>
     */
    private function applicableSizes(Product $product): EloquentCollection
    {
        $category = $product->relationLoaded('category')
            ? $product->category
            : $product->load('category')->category;

        if ($category === null) {
            return new EloquentCollection;
        }

        return $category->applicableSizes()
            ->filter(fn (Size $size): bool => $size->status
                && (int) $size->id !== (int) $product->base_size_id)
            ->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ])
            ->values() ?? new EloquentCollection;
    }

    /**
     * @param  array<string, mixed>  $combination
     */
    private function combinationExists(
        Product $product,
        array $combination,
        ?ProductVariant $variant = null,
    ): bool {
        return $product->variants()
            ->where(fn ($query) => $combination['size_id'] === null
                ? $query->whereNull('size_id')
                : $query->where('size_id', $combination['size_id']))
            ->where(fn ($query) => $combination['material_id'] === null
                ? $query->whereNull('material_id')
                : $query->where('material_id', $combination['material_id']))
            ->where(fn ($query) => $combination['stone_id'] === null
                ? $query->whereNull('stone_id')
                : $query->where('stone_id', $combination['stone_id']))
            ->where(fn ($query) => $combination['karat'] === null
                ? $query->whereNull('karat')
                : $query->where('karat', $combination['karat']))
            ->where(fn ($query) => $combination['metal_color'] === null
                ? $query->whereNull('metal_color')
                : $query->where('metal_color', $combination['metal_color']))
            ->when($variant, fn ($query) => $query->whereKeyNot($variant->getKey()))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $combination
     */
    private function hasNoVariantAttributes(array $combination): bool
    {
        return $combination['size_id'] === null
            && $combination['material_id'] === null
            && $combination['stone_id'] === null
            && $combination['karat'] === null
            && $combination['metal_color'] === null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function uniqueSku(
        Product $product,
        array $attributes,
        ?ProductVariant $variant = null,
    ): string {
        $baseSku = $this->normalizeSkuPart($product->base_sku ?: $product->code, 40);
        $parts = [$baseSku];

        if ($attributes['size_id'] !== null) {
            $size = Size::findOrFail($attributes['size_id']);
            $parts[] = 'T'.$this->normalizeSkuPart($size->name, 8);
        }

        if ($attributes['material_id'] !== null) {
            $material = Material::find($attributes['material_id']);

            if ($material !== null) {
                $parts[] = $this->normalizeSkuPart($material->name, 8);
            }
        }

        if ($attributes['stone_id'] !== null) {
            $stone = Stone::find($attributes['stone_id']);

            if ($stone !== null) {
                $parts[] = $this->normalizeSkuPart($stone->name, 3);
            }
        }

        if ($attributes['karat'] !== null) {
            $parts[] = $attributes['karat'].'K';
        }

        if ($attributes['metal_color'] !== null) {
            $parts[] = $this->normalizeSkuPart($attributes['metal_color'], 8);
        }

        $baseCandidate = implode('-', $parts);
        $candidate = $baseCandidate;
        $suffix = 2;

        while (
            ProductVariant::withTrashed()
                ->where('sku', $candidate)
                ->when($variant, fn ($query) => $query->whereKeyNot($variant->getKey()))
                ->exists()
        ) {
            $candidate = $baseCandidate.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function normalizeSkuPart(string $value, int $limit): string
    {
        $normalized = Str::upper(Str::ascii(trim($value)));
        $normalized = (string) preg_replace('/[^A-Z0-9]+/', '-', $normalized);

        return trim(Str::substr($normalized, 0, $limit), '-');
    }

    private function ensureVariantBelongsToProduct(Product $product, ProductVariant $variant): void
    {
        abort_unless($variant->product_id === $product->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function productContext(Product $product): array
    {
        /** @var ProductImage|null $image */
        $image = $product->images->first();

        return [
            'name' => $product->name,
            'base_sku' => $product->base_sku,
            'category' => $product->category?->name,
            'imageUrl' => $image?->publicUrl(),
            'imagePath' => $image !== null
                ? ltrim((string) parse_url($image->publicUrl(), PHP_URL_PATH), '/')
                : null,
            'imageExists' => $image?->hasPublicFile() ?? false,
        ];
    }
}
