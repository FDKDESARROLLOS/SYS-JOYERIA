<?php

namespace App\Services;

use App\Exceptions\InventoryReservationException;
use App\Models\InventoryReservation;
use App\Models\InventoryStock;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Support\InventoryReservationStatus;
use Illuminate\Support\Facades\DB;

class InventoryReservationService
{
    public function reserve(OrderItem $orderItem): InventoryReservation
    {
        return DB::transaction(function () use ($orderItem): InventoryReservation {
            $lockedOrderItem = OrderItem::query()
                ->lockForUpdate()
                ->findOrFail($orderItem->getKey());

            if (InventoryReservation::query()
                ->where('order_item_id', $lockedOrderItem->getKey())
                ->exists()) {
                throw InventoryReservationException::alreadyExists();
            }

            $variant = ProductVariant::query()
                ->lockForUpdate()
                ->find($lockedOrderItem->product_variant_id);

            if ($variant === null || $variant->status !== 'active') {
                throw InventoryReservationException::variantUnavailable();
            }

            $stock = InventoryStock::query()
                ->lockForUpdate()
                ->where('product_variant_id', $variant->getKey())
                ->first();

            if (
                $stock === null
                || (int) $stock->product_variant_id !== (int) $variant->getKey()
                || $lockedOrderItem->quantity < 1
            ) {
                throw InventoryReservationException::insufficientStock();
            }

            if ($stock->available_stock < $lockedOrderItem->quantity) {
                throw InventoryReservationException::insufficientStock();
            }

            $stock->increment('reserved_stock', $lockedOrderItem->quantity);

            return InventoryReservation::query()->create([
                'order_id' => $lockedOrderItem->order_id,
                'order_item_id' => $lockedOrderItem->id,
                'product_variant_id' => $lockedOrderItem->product_variant_id,
                'inventory_stock_id' => $stock->id,
                'quantity' => $lockedOrderItem->quantity,
                'status' => InventoryReservationStatus::ACTIVE,
                'reserved_at' => now(),
            ]);
        });
    }

    public function release(InventoryReservation $reservation): InventoryReservation
    {
        return $this->releaseReservation($reservation, InventoryReservationStatus::RELEASED);
    }

    public function expire(InventoryReservation $reservation): InventoryReservation
    {
        return $this->releaseReservation($reservation, InventoryReservationStatus::EXPIRED, true);
    }

    private function releaseReservation(
        InventoryReservation $reservation,
        string $status,
        bool $requireExpiration = false,
    ): InventoryReservation {
        return DB::transaction(function () use ($reservation, $status, $requireExpiration): InventoryReservation {
            $lockedReservation = InventoryReservation::query()
                ->lockForUpdate()
                ->findOrFail($reservation->getKey());

            if (! $lockedReservation->isActive()) {
                return $lockedReservation;
            }

            if (
                $requireExpiration
                && ($lockedReservation->expires_at === null || $lockedReservation->expires_at->isFuture())
            ) {
                return $lockedReservation;
            }

            $stock = InventoryStock::query()
                ->lockForUpdate()
                ->findOrFail($lockedReservation->inventory_stock_id);

            $orderItem = OrderItem::query()->findOrFail($lockedReservation->order_item_id);

            if (
                (int) $orderItem->order_id !== (int) $lockedReservation->order_id
                || (int) $orderItem->product_variant_id !== (int) $lockedReservation->product_variant_id
                || (int) $orderItem->quantity !== (int) $lockedReservation->quantity
                || (int) $stock->product_variant_id !== (int) $lockedReservation->product_variant_id
                || $stock->reserved_stock < $lockedReservation->quantity
            ) {
                throw InventoryReservationException::inconsistent();
            }

            $stock->decrement('reserved_stock', $lockedReservation->quantity);
            $lockedReservation->update([
                'status' => $status,
                'released_at' => now(),
            ]);

            return $lockedReservation->refresh();
        });
    }

    public function consume(InventoryReservation $reservation): InventoryReservation
    {
        return DB::transaction(function () use ($reservation): InventoryReservation {
            $lockedReservation = InventoryReservation::query()
                ->lockForUpdate()
                ->findOrFail($reservation->getKey());

            if (! $lockedReservation->isActive()) {
                throw InventoryReservationException::inactive();
            }

            $stock = InventoryStock::query()
                ->lockForUpdate()
                ->findOrFail($lockedReservation->inventory_stock_id);

            $orderItem = OrderItem::query()->findOrFail($lockedReservation->order_item_id);

            if (
                $orderItem->order_id !== $lockedReservation->order_id
                || $orderItem->product_variant_id !== $lockedReservation->product_variant_id
                || $orderItem->quantity !== $lockedReservation->quantity
                || $stock->product_variant_id !== $lockedReservation->product_variant_id
                || $stock->reserved_stock < $lockedReservation->quantity
                || $stock->current_stock < $lockedReservation->quantity
            ) {
                throw InventoryReservationException::inconsistent();
            }

            $previousStock = (int) $stock->current_stock;
            $resultingStock = $previousStock - $lockedReservation->quantity;
            $orderNumber = $lockedReservation->order?->order_number ?? $lockedReservation->order_id;

            $stock->update([
                'current_stock' => $resultingStock,
                'reserved_stock' => $stock->reserved_stock - $lockedReservation->quantity,
            ]);

            $stock->movements()->create([
                'user_id' => auth()->id(),
                'type' => 'Salida',
                'quantity' => $lockedReservation->quantity,
                'previous_stock' => $previousStock,
                'resulting_stock' => $resultingStock,
                'reason' => "Consumo de reserva del pedido {$orderNumber}",
                'reference' => (string) $orderNumber,
                'movement_at' => now(),
            ]);

            $lockedReservation->update([
                'status' => InventoryReservationStatus::CONSUMED,
                'consumed_at' => now(),
            ]);

            return $lockedReservation->refresh();
        });
    }
}
