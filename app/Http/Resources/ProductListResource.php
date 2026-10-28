<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Support\CatalogPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
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
        $available = $variants->contains(fn ($variant): bool => ($variant->inventoryStock?->available_stock ?? 0) > 0);

        return [
            'slug' => $product->slug,
            'name' => $product->name,
            'subtitle' => $product->subtitle,
            'short_description' => $product->short_description,
            'category' => new CategoryResource($product->category),
            'image' => $images->isNotEmpty() ? new ProductImageResource($images->first()) : null,
            'price' => CatalogPrice::summary($variants->map(
                fn ($variant): ?string => $variant->effectivePrice($product),
            )),
            'sale_price' => CatalogPrice::summary($variants->map(
                fn ($variant): ?string => $variant->effectiveSalePrice($product),
            )),
            'availability' => [
                'available' => $available,
                'status' => $available ? 'in_stock' : 'out_of_stock',
            ],
        ];
    }
}
