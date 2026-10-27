<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
