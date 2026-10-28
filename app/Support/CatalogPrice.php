<?php

namespace App\Support;

final class CatalogPrice
{
    public const CURRENCY = 'MXN';

    /**
     * @param  iterable<int, string|null>  $amounts
     * @return array{from: string, to: string, currency: string}|null
     */
    public static function summary(iterable $amounts): ?array
    {
        $normalizedAmounts = [];

        foreach ($amounts as $amount) {
            if ($amount !== null) {
                $normalizedAmounts[] = self::normalize($amount);
            }
        }

        if ($normalizedAmounts === []) {
            return null;
        }

        usort($normalizedAmounts, self::compare(...));

        return [
            'from' => $normalizedAmounts[0],
            'to' => $normalizedAmounts[array_key_last($normalizedAmounts)],
            'currency' => self::CURRENCY,
        ];
    }

    /**
     * @return array{amount: string, currency: string}|null
     */
    public static function money(?string $amount): ?array
    {
        return $amount === null
            ? null
            : [
                'amount' => self::normalize($amount),
                'currency' => self::CURRENCY,
            ];
    }

    public static function normalizeOptional(?string $amount): ?string
    {
        if ($amount === null || trim($amount) === '') {
            return null;
        }

        $normalized = self::normalize($amount);

        return self::compare($normalized, '0.00') === 0 ? null : $normalized;
    }

    public static function isValidSalePrice(?string $salePrice, ?string $price): bool
    {
        $salePrice = self::normalizeOptional($salePrice);
        $price = self::normalizeOptional($price);

        return $salePrice !== null
            && $price !== null
            && self::compare($salePrice, $price) < 0;
    }

    private static function normalize(string $amount): string
    {
        [$integer, $decimal] = array_pad(explode('.', trim($amount), 2), 2, '0');
        $integer = ltrim($integer, '0') ?: '0';

        return $integer.'.'.str_pad(substr($decimal, 0, 2), 2, '0');
    }

    private static function compare(string $left, string $right): int
    {
        [$leftInteger, $leftDecimal] = explode('.', $left);
        [$rightInteger, $rightDecimal] = explode('.', $right);

        if (strlen($leftInteger) !== strlen($rightInteger)) {
            return strlen($leftInteger) <=> strlen($rightInteger);
        }

        return strcmp($left.$leftDecimal, $right.$rightDecimal);
    }
}
