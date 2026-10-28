<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'url' => $this->publicUrl(),
            'alt' => $this->alt ?: null,
            'is_cover' => (bool) $this->is_cover,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
