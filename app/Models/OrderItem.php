<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_variant_id',
    'product_name_snapshot',
    'sku_snapshot',
    'variant_label_snapshot',
    'selected_material',
    'selected_karat',
    'selected_metal_color',
    'selected_stone_name',
    'selected_stone_carats',
    'selected_size_name',
    'unit_price',
    'quantity',
    'subtotal',
])]
class OrderItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'selected_karat' => 'integer',
            'selected_stone_carats' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
