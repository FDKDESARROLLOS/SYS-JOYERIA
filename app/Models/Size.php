<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'sort_order',
    'status',
])]
class Size extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_size')->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'base_size_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'size_id');
    }
}
