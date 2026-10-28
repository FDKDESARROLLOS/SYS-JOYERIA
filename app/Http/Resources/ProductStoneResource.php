<?php

namespace App\Http\Resources;

use App\Models\ProductStone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductStoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductStone $productStone */
        $productStone = $this->resource;

        if ($productStone->position === 'lateral') {
            return [
                'enabled' => true,
                'name' => $productStone->stone?->name,
                'shape' => $productStone->shape,
                'carats_per_stone' => $productStone->carats,
                'quantity' => $productStone->quantity,
                'origin' => $productStone->origin,
                'certification' => $productStone->certification,
                'color' => $productStone->color,
                'clarity' => $productStone->clarity,
                'cut' => $productStone->cut,
            ];
        }

        return [
            'name' => $productStone->stone?->name,
            'shape' => $productStone->shape,
            'carats' => $productStone->carats,
            'origin' => $productStone->origin,
            'certification' => $productStone->certification,
            'color' => $productStone->color,
            'clarity' => $productStone->clarity,
            'cut' => $productStone->cut,
        ];
    }
}
