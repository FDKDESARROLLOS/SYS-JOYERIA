<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_variant_id',
    'current_stock',
    'reserved_stock',
    'minimum_stock',
])]
class InventoryStock extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'current_stock' => 'integer',
            'reserved_stock' => 'integer',
            'minimum_stock' => 'integer',
        ];
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) $this->current_stock - (int) $this->reserved_stock);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'inventory_stock_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class, 'inventory_stock_id');
    }
}
