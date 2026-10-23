<?php

namespace App\Support;

use BackedEnum;
use JsonSerializable;
use Stringable;

final class ErrorContextSanitizer
{
    private const REDACTED = '[REDACTED]';

    private const MAX_DEPTH = 4;

    private const MAX_ITEMS = 100;

    private const MAX_STRING_LENGTH = 2000;

    /**
     * @param  array<mixed>  $context
     * @return array<string|int, mixed>
     */
    public function sanitize(array $context): array
    {
        return $this->sanitizeValue($context, 0);
    }

    public function sanitizeMessage(string $message): string
    {
        $message = preg_replace('/(Authorization\s*:\s*)(Bearer\s+)?[^\r\n,;]+/i', '$1$2'.self::REDACTED, $message) ?? $message;
        $message = preg_replace('/(Cookie\s*:\s*)[^\r\n]+/i', '$1'.self::REDACTED, $message) ?? $message;
        $message = preg_replace(
            '/(["\']?(?:password(?:_confirmation|_current)?|current_password|token|access_token|refresh_token|api_key|secret|stripe_secret|authorization|cookie|cookies|session(?:_id)?|remember_token|_token)["\']?\s*(?:=>|:|=)\s*)(?!Bearer\b)(?:"[^"]*"|\'[^\']*\'|[^\s,;}\]]+)/i',
            '$1'.self::REDACTED,
            $message,
        ) ?? $message;
        $message = preg_replace(
            '/([?&](?:password|token|access_token|refresh_token|api_key|secret|session|session_id)=)[^&\s]+/i',
            '$1'.self::REDACTED,
            $message,
        ) ?? $message;
        $message = preg_replace('/(Bearer\s+)[^\s]+/i', '$1'.self::REDACTED, $message) ?? $message;

        return $this->limitString($message);
    }

    private function sanitizeValue(mixed $value, int $depth): mixed
    {
        if ($depth >= self::MAX_DEPTH) {
            return '[TRUNCATED]';
        }

        if (is_array($value)) {
            $sanitized = [];
            $items = 0;

            foreach ($value as $key => $item) {
                if ($items >= self::MAX_ITEMS) {
                    $sanitized['[TRUNCATED]'] = '[TRUNCATED]';
                    break;
                }

                $normalizedKey = strtolower(str_replace('-', '_', (string) $key));
                $sanitized[$key] = $this->isSensitiveKey($normalizedKey)
                    ? self::REDACTED
                    : $this->sanitizeValue($item, $depth + 1);
                $items++;
            }

            return $sanitized;
        }

        if ($value instanceof BackedEnum) {
            return $this->sanitizeValue($value->value, $depth + 1);
        }

        if ($value instanceof JsonSerializable) {
            return $this->sanitizeValue($value->jsonSerialize(), $depth + 1);
        }

        if (is_object($value)) {
            return '[OBJECT '.get_class($value).']';
        }

        if (is_resource($value)) {
            return '[RESOURCE]';
        }

        if ($value instanceof Stringable) {
            return $this->sanitizeMessage((string) $value);
        }

        if (is_string($value)) {
            return $this->sanitizeMessage($value);
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return '[UNSERIALIZABLE]';
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array($key, [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'access_token',
            'refresh_token',
            'api_key',
            'secret',
            'stripe_secret',
            'authorization',
            'cookie',
            'cookies',
            'session',
            'session_id',
            'remember_token',
            'csrf_token',
            '_token',
        ], true)
            || str_ends_with($key, '_token')
            || str_contains($key, 'secret')
            || str_contains($key, 'authorization')
            || str_contains($key, 'cookie')
            || str_contains($key, 'session');
    }

    private function limitString(string $value): string
    {
        return mb_strlen($value) > self::MAX_STRING_LENGTH
            ? mb_substr($value, 0, self::MAX_STRING_LENGTH).'…'
            : $value;
    }
}
