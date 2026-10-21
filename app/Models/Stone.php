<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'status',
])]
class Stone extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function productStones(): HasMany
    {
        return $this->hasMany(ProductStone::class, 'stone_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'stone_id');
    }
}
