<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CatalogPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;
        $variants = $product->variants;
        $images = $product->images->filter(fn ($image): bool => $image->hasPublicFile())->values();
        $centralStone = $product->productStones
            ->where('position', 'central')
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->first();
        $lateralStones = $product->productStones
            ->where('position', 'lateral')
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $variants->each(fn (ProductVariant $variant): ProductVariant => $variant->setRelation('product', $product));

        return [
            'slug' => $product->slug,
            'name' => $product->name,
            'model' => $product->model,
            'subtitle' => $product->subtitle,
            'short_description' => $product->short_description,
            'category' => new CategoryResource($product->category),
            'collection' => $product->collection !== null
                ? [
                    'name' => $product->collection->name,
                    'slug' => $product->collection->slug,
                ]
                : null,
            'images' => ProductImageResource::collection($images),
            'stones' => [
                'center' => $centralStone !== null
                    ? new ProductStoneResource($centralStone)
                    : null,
                'side' => ProductStoneResource::collection($lateralStones),
            ],
            'price' => CatalogPrice::summary($variants->map(
                fn ($variant): ?string => $variant->effectivePrice($product),
            )),
            'sale_price' => CatalogPrice::summary($variants->map(
                fn ($variant): ?string => $variant->effectiveSalePrice($product),
            )),
            'variants' => ProductVariantResource::collection($variants),
        ];
    }
}
