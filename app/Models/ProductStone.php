<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'stone_id',
    'position',
    'shape',
    'carats',
    'quantity',
    'origin',
    'certification',
    'color',
    'clarity',
    'cut',
])]
class ProductStone extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'carats' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stone(): BelongsTo
    {
        return $this->belongsTo(Stone::class, 'stone_id');
    }
}
