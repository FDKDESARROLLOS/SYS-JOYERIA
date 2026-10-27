<?php

namespace App\Support;

final class InventoryReservationStatus
{
    public const ACTIVE = 'active';

    public const RELEASED = 'released';

    public const CONSUMED = 'consumed';

    public const EXPIRED = 'expired';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::RELEASED,
            self::CONSUMED,
            self::EXPIRED,
        ];
    }
}
