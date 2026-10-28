<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingQuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'available' => $this->resource['available'],
            'country_code' => $this->resource['country_code'],
            'method' => $this->resource['method'],
            'subtotal' => $this->resource['subtotal'],
            'shipping' => $this->resource['shipping'],
            'free_shipping' => $this->resource['free_shipping'],
        ];
    }
}
