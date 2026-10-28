<?php

namespace Tests\Feature;

use App\Exceptions\InventoryReservationException;
use App\Models\Category;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\InventoryReservationService;
use App\Support\InventoryReservationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserving_order_item_increases_reserved_stock_without_changing_physical_stock(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 2);

        $this->assertSame(0, $stock->refresh()->reserved_stock);

        $reservation = app(InventoryReservationService::class)->reserve($orderItem);

        $this->assertSame(5, $stock->refresh()->current_stock);
        $this->assertSame(2, $stock->reserved_stock);
        $this->assertSame(3, $stock->available_stock);
        $this->assertSame(InventoryReservationStatus::ACTIVE, $reservation->status);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_reservation_is_unique_per_order_item(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(3, 2);
        $service = app(InventoryReservationService::class);

        $service->reserve($orderItem);

        try {
            $service->reserve($orderItem);
            $this->fail('Expected a duplicate reservation exception.');
        } catch (InventoryReservationException $exception) {
            $this->assertSame(
                'El artículo del pedido ya tiene una reserva registrada.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(2, $stock->refresh()->reserved_stock);

    }

    public function test_reservation_rejects_insufficient_stock_without_changes(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(3, 4);

        try {
            app(InventoryReservationService::class)->reserve($orderItem);
            $this->fail('Expected an insufficient stock exception.');
        } catch (InventoryReservationException $exception) {
            $this->assertSame(
                'No hay stock disponible suficiente para reservar la cantidad solicitada.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(0, $stock->refresh()->reserved_stock);
    }

    public function test_inventory_stock_rejects_reserved_stock_above_physical_stock(): void
    {
        [$stock] = $this->createOrderItem(3, 1);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El stock reservado debe estar entre cero y el stock físico actual.');

        $stock->update(['reserved_stock' => 4]);
    }

    public function test_reservation_rejects_an_inactive_variant(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(3, 1);
        $orderItem->productVariant->update(['status' => 'inactive']);

        $this->expectException(InventoryReservationException::class);
        $this->expectExceptionMessage('La variante no está disponible para reservarse.');

        app(InventoryReservationService::class)->reserve($orderItem);

        $this->assertSame(0, $stock->refresh()->reserved_stock);
    }

    public function test_reservation_rejects_a_soft_deleted_variant(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(3, 1);
        $orderItem->productVariant->delete();

        $this->expectException(InventoryReservationException::class);
        $this->expectExceptionMessage('La variante no está disponible para reservarse.');

        app(InventoryReservationService::class)->reserve($orderItem);

        $this->assertSame(0, $stock->refresh()->reserved_stock);
    }

    public function test_release_is_idempotent_and_does_not_change_physical_stock(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 2);
        $service = app(InventoryReservationService::class);
        $reservation = $service->reserve($orderItem);

        $released = $service->release($reservation);
        $service->release($released);

        $this->assertSame(5, $stock->refresh()->current_stock);
        $this->assertSame(0, $stock->reserved_stock);
        $this->assertSame(InventoryReservationStatus::RELEASED, $released->status);
    }

    public function test_expired_reservation_releases_reserved_stock_without_changing_physical_stock(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 2);
        $service = app(InventoryReservationService::class);
        $reservation = $service->reserve($orderItem);
        $reservation->update(['expires_at' => now()->subMinute()]);

        $expired = $service->expire($reservation);
        $service->expire($expired);

        $this->assertSame(5, $stock->refresh()->current_stock);
        $this->assertSame(0, $stock->reserved_stock);
        $this->assertSame(InventoryReservationStatus::EXPIRED, $expired->status);
        $this->assertNotNull($expired->released_at);
    }

    public function test_expired_reservations_can_be_processed_by_the_artisan_command(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 2);
        $reservation = app(InventoryReservationService::class)->reserve($orderItem);
        $reservation->update(['expires_at' => now()->subMinute()]);

        $this->artisan('inventory:release-expired-reservations')
            ->expectsOutput('Reservas expiradas liberadas: 1')
            ->assertSuccessful();

        $this->assertSame(5, $stock->refresh()->current_stock);
        $this->assertSame(0, $stock->reserved_stock);
        $this->assertSame(InventoryReservationStatus::EXPIRED, $reservation->refresh()->status);

        Artisan::call('inventory:release-expired-reservations');
        $this->assertSame(0, $stock->refresh()->reserved_stock);
    }

    public function test_expiration_does_not_change_a_consumed_reservation(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 2);
        $service = app(InventoryReservationService::class);
        $reservation = $service->reserve($orderItem);
        $consumed = $service->consume($reservation);
        $consumed->update(['expires_at' => now()->subMinute()]);

        $result = $service->expire($consumed);

        $this->assertSame(3, $stock->refresh()->current_stock);
        $this->assertSame(0, $stock->reserved_stock);
        $this->assertSame(InventoryReservationStatus::CONSUMED, $result->status);
    }

    public function test_release_rejects_a_reservation_with_stock_from_another_variant(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 2);
        $reservation = app(InventoryReservationService::class)->reserve($orderItem);
        $otherVariant = ProductVariant::create([
            'product_id' => $orderItem->productVariant->product_id,
            'sku' => 'OTHER-'.Str::random(8),
            'status' => 'active',
        ]);
        $otherStock = InventoryStock::create([
            'product_variant_id' => $otherVariant->id,
            'current_stock' => 4,
        ]);
        DB::table('inventory_reservations')
            ->where('id', $reservation->id)
            ->update(['inventory_stock_id' => $otherStock->id]);

        $this->expectException(InventoryReservationException::class);
        $this->expectExceptionMessage('La reserva no coincide con el inventario relacionado.');

        app(InventoryReservationService::class)->release($reservation);

        $this->assertSame(2, $stock->refresh()->reserved_stock);
        $this->assertSame(4, $otherStock->refresh()->reserved_stock);
    }

    public function test_consume_reduces_physical_and_reserved_stock_and_records_exit(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 2);
        $service = app(InventoryReservationService::class);
        $reservation = $service->reserve($orderItem);

        $consumed = $service->consume($reservation);

        $this->assertSame(3, $stock->refresh()->current_stock);
        $this->assertSame(0, $stock->reserved_stock);
        $this->assertSame(InventoryReservationStatus::CONSUMED, $consumed->status);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_stock_id' => $stock->id,
            'type' => 'Salida',
            'quantity' => 2,
            'previous_stock' => 5,
            'resulting_stock' => 3,
        ]);
    }

    public function test_consuming_a_reservation_twice_is_rejected(): void
    {
        [, $orderItem] = $this->createOrderItem(5, 2);
        $service = app(InventoryReservationService::class);
        $reservation = $service->reserve($orderItem);
        $service->consume($reservation);

        $this->expectException(InventoryReservationException::class);
        $service->consume($reservation);
    }

    public function test_manual_exit_respects_available_stock(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 4);
        app(InventoryReservationService::class)->reserve($orderItem);

        $this->actingAs(User::factory()->create())
            ->post(route('inventory.movements.variant-store', $stock), [
                ...$this->movementPayload('Salida', 2),
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(5, $stock->refresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_manual_adjustment_cannot_go_below_reserved_stock(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 4);
        app(InventoryReservationService::class)->reserve($orderItem);

        $this->actingAs(User::factory()->create())
            ->post(route('inventory.movements.context-store', $stock), [
                'inventory_stock_id' => $stock->id,
                ...$this->movementPayload('Ajuste', 2),
            ])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(5, $stock->refresh()->current_stock);
    }

    public function test_entry_and_return_do_not_change_reserved_stock(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 2);
        app(InventoryReservationService::class)->reserve($orderItem);
        $admin = User::factory()->create();

        foreach (['Entrada', 'Devolución'] as $type) {
            $this->actingAs($admin)
                ->post(route('inventory.movements.variant-store', $stock), $this->movementPayload($type, 1))
                ->assertRedirect();
        }

        $this->assertSame(2, $stock->refresh()->reserved_stock);
        $this->assertSame(7, $stock->current_stock);
    }

    public function test_active_reservation_blocks_product_and_variant_deletion(): void
    {
        [$stock, $orderItem] = $this->createOrderItem(5, 1);
        app(InventoryReservationService::class)->reserve($orderItem);
        $admin = User::factory()->create();
        $variant = $stock->productVariant;

        $this->actingAs($admin)
            ->delete(route('products.variants.destroy', [$variant->product, $variant]))
            ->assertSessionHasErrors('variant');

        $this->actingAs($admin)
            ->delete(route('products.destroy', $variant->product))
            ->assertSessionHasErrors('product');

        $this->assertFalse($variant->fresh()->trashed());
        $this->assertFalse($variant->product->fresh()->trashed());
    }

    public function test_order_item_resolves_a_soft_deleted_variant_for_history(): void
    {
        [, $orderItem] = $this->createOrderItem(5, 1);
        $variant = $orderItem->productVariant;
        $variant->delete();

        $this->assertSame($variant->id, $orderItem->fresh()->productVariant->id);
        $this->assertTrue($orderItem->fresh()->productVariant->trashed());
    }

    /**
     * @return array{0: InventoryStock, 1: OrderItem}
     */
    private function createOrderItem(int $currentStock, int $quantity): array
    {
        $suffix = Str::lower(Str::random(8));
        $category = Category::create([
            'name' => 'Anillos '.$suffix,
            'slug' => 'anillos-'.$suffix,
            'status' => true,
            'sort_order' => 1,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto '.$suffix,
            'model' => 'Modelo '.$suffix,
            'code' => 'PRD-'.$suffix,
            'base_sku' => 'SKU-'.$suffix,
            'slug' => 'producto-'.$suffix,
            'price' => '100.00',
            'status' => 'active',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-'.$suffix,
            'status' => 'active',
        ]);
        $stock = InventoryStock::create([
            'product_variant_id' => $variant->id,
            'current_stock' => $currentStock,
        ]);
        $order = Order::create([
            'order_number' => 'ORD-'.$suffix,
            'customer_name' => 'Cliente '.$suffix,
            'customer_email' => $suffix.'@example.com',
            'shipping_address' => 'Calle 1',
            'shipping_city' => 'Ciudad',
            'shipping_state' => 'Estado',
            'shipping_postal_code' => '00000',
            'shipping_country' => 'MX',
            'subtotal' => '100.00',
            'total' => '100.00',
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $variant->sku,
            'unit_price' => '100.00',
            'quantity' => $quantity,
            'subtotal' => (string) ($quantity * 100).'.00',
        ]);

        return [$stock, $orderItem];
    }

    /**
     * @return array<string, mixed>
     */
    private function movementPayload(string $type, int $quantity): array
    {
        return [
            'type' => $type,
            'quantity' => $quantity,
            'reason' => 'Prueba de reserva',
            'reference' => 'RES-001',
            'movement_at' => now()->format('Y-m-d H:i:s'),
            'adjustment_type' => $type === 'Ajuste' ? 'decrease' : null,
        ];
    }
}
