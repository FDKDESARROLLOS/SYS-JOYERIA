<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Support\CatalogPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->product;
        $availableStock = $this->inventoryStock?->available_stock ?? 0;

        return [
            'sku' => $this->sku,
            'attributes' => $this->effectiveAttributes($product),
            'price' => CatalogPrice::money($this->effectivePrice($product)),
            'sale_price' => CatalogPrice::money($this->effectiveSalePrice($product)),
            'available_stock' => $availableStock,
            'availability' => [
                'available' => $availableStock > 0,
                'status' => $availableStock > 0 ? 'in_stock' : 'out_of_stock',
            ],
        ];
    }
}
