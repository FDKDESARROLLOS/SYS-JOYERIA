<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'enabled',
    'method_name',
    'estimated_time',
    'shipping_cost',
    'free_shipping_enabled',
    'free_shipping_type',
    'free_shipping_minimum',
    'notes',
])]
class ShippingSetting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'shipping_cost' => 'decimal:2',
            'free_shipping_enabled' => 'boolean',
            'free_shipping_minimum' => 'decimal:2',
        ];
    }

    /**
     * Resolve the configured fixed shipping cost without integrating checkout yet.
     *
     * A null result means shipping is disabled. Values are compared in cents to
     * avoid relying on direct floating-point comparisons.
     */
    public function calculateShippingCost(int|float|string $subtotal): ?string
    {
        if (! $this->enabled) {
            return null;
        }

        $shippingCostCents = $this->decimalToCents($this->shipping_cost);

        if (! $this->free_shipping_enabled) {
            return $this->formatCents($shippingCostCents);
        }

        if ($this->free_shipping_type === 'all') {
            return '0.00';
        }

        if (
            $this->free_shipping_type === 'minimum'
            && $this->decimalToCents($subtotal) >= $this->decimalToCents($this->free_shipping_minimum)
        ) {
            return '0.00';
        }

        return $this->formatCents($shippingCostCents);
    }

    private function decimalToCents(int|float|string|null $value): int
    {
        return (int) round((float) $value * 100);
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
