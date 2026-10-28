<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShippingQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'country_code' => [
                'required',
                'string',
                'size:2',
                Rule::in(['MX']),
            ],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['required', 'array:sku,quantity'],
            'items.*.sku' => ['required', 'string', 'max:255', 'distinct:strict'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
