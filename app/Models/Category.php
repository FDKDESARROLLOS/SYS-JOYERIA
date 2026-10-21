<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'parent_id',
    'name',
    'slug',
    'status',
    'sort_order',
])]
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isEffectivelyActive(): bool
    {
        if (! $this->status) {
            return false;
        }

        if ($this->parent_id === null) {
            return true;
        }

        $parent = $this->parent()
            ->withTrashed()
            ->first();

        return $parent !== null
            && $parent->deleted_at === null
            && (bool) $parent->status;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'category_size')->withTimestamps();
    }

    /**
     * Return sizes assigned directly to this category or inherited from its parent.
     *
     * @return Collection<int, Size>
     */
    public function applicableSizes(): Collection
    {
        $sourceCategory = $this->parent_id === null ? $this : $this->parent;

        return $sourceCategory?->sizes()->get() ?? new Collection;
    }
}
