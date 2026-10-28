<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Support\CatalogPrice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ShippingQuoteService
{
    /**
     * @param  array{country_code: string, items: array<int, array{sku: string, quantity: int}>}  $payload
     * @return array{
     *     available: bool,
     *     country_code: string,
     *     method: ?array{name: string, estimated_time: ?string},
     *     subtotal: array{amount: string, currency: string},
     *     shipping: ?array{amount: string, currency: string},
     *     free_shipping: bool,
     * }
     */
    public function quote(array $payload): array
    {
        $itemsBySku = collect($payload['items'])->keyBy('sku');
        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('sku', $itemsBySku->keys())
            ->publiclyVisible()
            ->whereHas('product', fn (Builder $query): Builder => $query->publiclyVisible())
            ->get()
            ->keyBy('sku');

        $missingSkus = $itemsBySku->keys()->diff($variants->keys())->values();

        if ($missingSkus->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => [
                    'Uno o más SKU no están disponibles para cotizar: '.$missingSkus->implode(', ').'.',
                ],
            ]);
        }

        $subtotalCents = 0;

        foreach ($itemsBySku as $sku => $item) {
            $variant = $variants->get($sku);
            $price = $variant->effectiveSalePrice($variant->product)
                ?? $variant->effectivePrice($variant->product);

            if ($price === null) {
                throw ValidationException::withMessages([
                    'items' => ["El SKU {$sku} no tiene un precio comercial válido."],
                ]);
            }

            $subtotalCents += $this->decimalToCents($price) * $item['quantity'];
        }

        $subtotal = $this->formatCents($subtotalCents);
        $shippingSetting = ShippingSetting::query()->first();

        if ($shippingSetting === null || ! $shippingSetting->enabled) {
            return $this->unavailableQuote($payload['country_code'], $subtotal);
        }

        $shippingCost = $shippingSetting->calculateShippingCost($subtotal);

        return [
            'available' => true,
            'country_code' => $payload['country_code'],
            'method' => [
                'name' => $shippingSetting->method_name,
                'estimated_time' => $shippingSetting->estimated_time,
            ],
            'subtotal' => [
                'amount' => $subtotal,
                'currency' => CatalogPrice::CURRENCY,
            ],
            'shipping' => [
                'amount' => $shippingCost,
                'currency' => CatalogPrice::CURRENCY,
            ],
            'free_shipping' => $shippingSetting->free_shipping_enabled
                && $shippingCost === '0.00',
        ];
    }

    /**
     * @return array{
     *     available: false,
     *     country_code: string,
     *     method: null,
     *     subtotal: array{amount: string, currency: string},
     *     shipping: null,
     *     free_shipping: false,
     * }
     */
    private function unavailableQuote(string $countryCode, string $subtotal): array
    {
        return [
            'available' => false,
            'country_code' => $countryCode,
            'method' => null,
            'subtotal' => [
                'amount' => $subtotal,
                'currency' => CatalogPrice::CURRENCY,
            ],
            'shipping' => null,
            'free_shipping' => false,
        ];
    }

    private function decimalToCents(string $amount): int
    {
        [$integer, $decimal] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $integer * 100) + (int) str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
