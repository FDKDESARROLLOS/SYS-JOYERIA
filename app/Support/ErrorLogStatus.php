<?php

namespace App\Support;

final class ErrorLogStatus
{
    public const PENDING = 'pending';

    public const REVIEWED = 'reviewed';

    public const RESOLVED = 'resolved';

    public const IGNORED = 'ignored';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [
            self::PENDING,
            self::REVIEWED,
            self::RESOLVED,
            self::IGNORED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::PENDING => 'Pendiente',
            self::REVIEWED => 'Revisado',
            self::RESOLVED => 'Resuelto',
            self::IGNORED => 'Ignorado',
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, match ($from) {
            self::PENDING => [self::REVIEWED, self::RESOLVED, self::IGNORED],
            self::REVIEWED => [self::RESOLVED, self::IGNORED],
            self::RESOLVED, self::IGNORED => [self::REVIEWED],
            default => [],
        }, true);
    }
}
