<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PublicCatalogService
{
    public function paginateProducts(): LengthAwarePaginator
    {
        return Product::query()
            ->publiclyVisible()
            ->with($this->listRelations())
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();
    }

    public function findProductBySlug(string $slug): Product
    {
        return Product::query()
            ->publiclyVisible()
            ->with($this->detailRelations())
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @return array<string, \Closure|string>
     */
    private function listRelations(): array
    {
        return [
            'category:id,parent_id,name,slug',
            'collection' => fn ($query) => $query
                ->where('status', true)
                ->select(['id', 'name', 'slug']),
            'images' => fn ($query) => $query
                ->orderByDesc('is_cover')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'variants' => fn ($query) => $query
                ->publiclyVisible()
                ->select(['id', 'product_id', 'price', 'sale_price'])
                ->with('inventoryStock:id,product_variant_id,current_stock,reserved_stock'),
        ];
    }

    /**
     * @return array<string, \Closure|string>
     */
    private function detailRelations(): array
    {
        return [
            'category:id,parent_id,name,slug',
            'collection' => fn ($query) => $query
                ->where('status', true)
                ->select(['id', 'name', 'slug']),
            'baseSize' => fn ($query) => $query
                ->where('status', true)
                ->select(['id', 'name']),
            'material' => fn ($query) => $query
                ->where('status', true)
                ->select(['id', 'name']),
            'productStones' => fn ($query) => $query
                ->select([
                    'id',
                    'product_id',
                    'stone_id',
                    'position',
                    'sort_order',
                    'shape',
                    'carats',
                    'quantity',
                    'stones_per_side',
                    'origin',
                    'certification',
                    'color',
                    'clarity',
                    'cut',
                ])
                ->whereHas('stone', fn ($stone) => $stone->where('status', true))
                ->orderBy('position')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->with(['stone' => fn ($stone) => $stone->where('status', true)->select(['id', 'name'])]),
            'images' => fn ($query) => $query
                ->orderByDesc('is_cover')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'variants' => fn ($query) => $query
                ->publiclyVisible()
                ->with([
                    'size' => fn ($size) => $size->where('status', true)->select(['id', 'name']),
                    'material' => fn ($material) => $material->where('status', true)->select(['id', 'name']),
                    'stone' => fn ($stone) => $stone->where('status', true)->select(['id', 'name']),
                    'inventoryStock:id,product_variant_id,current_stock,reserved_stock',
                ])
                ->orderBy('id'),
        ];
    }
}
