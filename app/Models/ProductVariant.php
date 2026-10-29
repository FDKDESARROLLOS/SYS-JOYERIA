<?php

namespace App\Models;

use App\Support\CatalogPrice;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'product_id',
    'size_id',
    'material_id',
    'stone_id',
    'sku',
    'karat',
    'metal_color',
    'cost',
    'price',
    'sale_price',
    'status',
])]
class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'karat' => 'integer',
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
        ];
    }

    public function setSalePriceAttribute(mixed $value): void
    {
        $this->attributes['sale_price'] = CatalogPrice::normalizeOptional(
            is_scalar($value) ? (string) $value : null,
        );
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->whereHas('inventoryStock');
    }

    public function effectivePrice(?Product $product = null): ?string
    {
        $product ??= $this->product;

        return $this->price ?? $product?->price;
    }

    public function effectiveSalePrice(?Product $product = null): ?string
    {
        $product ??= $this->product;

        $salePrice = CatalogPrice::normalizeOptional($this->sale_price);
        $salePrice ??= CatalogPrice::normalizeOptional($product?->sale_price);

        return CatalogPrice::isValidSalePrice($salePrice, $this->effectivePrice($product))
            ? $salePrice
            : null;
    }

    /**
     * @return array{size: ?string, material: ?string, karat: ?int, metal_color: ?string, stone: ?string}
     */
    public function effectiveAttributes(?Product $product = null): array
    {
        $product ??= $this->product;

        return [
            'size' => $this->size?->name ?? $product?->baseSize?->name,
            'material' => $this->material?->name ?? $product?->material?->name,
            'karat' => $this->karat ?? $product?->karat,
            'metal_color' => $this->metal_color ?? $product?->metal_color,
            'stone' => $this->stone?->name,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function stone(): BelongsTo
    {
        return $this->belongsTo(Stone::class, 'stone_id');
    }

    public function inventoryStock(): HasOne
    {
        return $this->hasOne(InventoryStock::class, 'product_variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'product_variant_id');
    }
}
