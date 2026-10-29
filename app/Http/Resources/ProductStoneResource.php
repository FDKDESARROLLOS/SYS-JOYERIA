<?php

namespace App\Http\Resources;

use App\Models\ProductStone;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductStoneResource extends JsonResource
{
    public function __construct($resource, private readonly bool $includeId = true)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductStone $productStone */
        $productStone = $this->resource;

        $data = [
            'role' => $productStone->resolvedRole(),
            'distribution' => $productStone->resolvedDistribution(),
            'name' => $productStone->stone?->name,
            'shape' => $productStone->shape,
            'carats_per_stone' => $productStone->carats,
            'quantity' => $productStone->quantity,
            'stones_per_side' => $productStone->stones_per_side,
            'origin' => $productStone->origin,
            'certification' => $productStone->certification,
            'color' => $productStone->color,
            'clarity' => $productStone->clarity,
            'cut' => $productStone->cut,
            'sort_order' => $productStone->sort_order,
        ];

        if ($this->includeId) {
            $data = ['id' => $productStone->id, ...$data];
        }

        return $data;
    }
}
