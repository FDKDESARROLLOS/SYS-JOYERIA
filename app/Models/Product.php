<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id',
    'collection_id',
    'base_size_id',
    'material_id',
    'name',
    'model',
    'code',
    'base_sku',
    'slug',
    'subtitle',
    'short_description',
    'karat',
    'metal_color',
    'price',
    'sale_price',
    'cost',
    'status',
    'featured',
    'is_new',
    'bestseller',
])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'karat' => 'integer',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'cost' => 'decimal:2',
            'featured' => 'boolean',
            'is_new' => 'boolean',
            'bestseller' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    public function baseSize(): BelongsTo
    {
        return $this->belongsTo(Size::class, 'base_size_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function productStones(): HasMany
    {
        return $this->hasMany(ProductStone::class, 'product_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }
}
