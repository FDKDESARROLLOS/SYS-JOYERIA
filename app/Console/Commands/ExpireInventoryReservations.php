<?php

namespace App\Console\Commands;

use App\Models\InventoryReservation;
use App\Services\InventoryReservationService;
use App\Support\InventoryReservationStatus;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('inventory:release-expired-reservations')]
#[Description('Libera las reservas de inventario vencidas.')]
class ExpireInventoryReservations extends Command
{
    public function handle(InventoryReservationService $reservationService): int
    {
        $processed = 0;
        $now = now();

        InventoryReservation::query()
            ->where('status', InventoryReservationStatus::ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use ($reservationService, &$processed): void {
                foreach ($reservations as $reservation) {
                    $released = $reservationService->expire($reservation);

                    if ($released->status === InventoryReservationStatus::EXPIRED) {
                        $processed++;
                    }
                }
            });

        $this->info("Reservas expiradas liberadas: {$processed}");

        return self::SUCCESS;
    }
}
