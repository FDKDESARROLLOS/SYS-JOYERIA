<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_number',
    'customer_name',
    'customer_email',
    'customer_phone',
    'shipping_address',
    'shipping_city',
    'shipping_state',
    'shipping_postal_code',
    'shipping_country',
    'subtotal',
    'shipping_cost',
    'iva',
    'total',
    'currency',
    'order_status',
    'notes',
    'placed_at',
])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class, 'order_id');
    }
}
