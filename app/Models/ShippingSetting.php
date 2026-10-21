<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'enabled',
    'method_name',
    'estimated_time',
    'shipping_cost',
    'free_shipping_enabled',
    'free_shipping_type',
    'free_shipping_minimum',
    'notes',
])]
class ShippingSetting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'shipping_cost' => 'decimal:2',
            'free_shipping_enabled' => 'boolean',
            'free_shipping_minimum' => 'decimal:2',
        ];
    }
}
