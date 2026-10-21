<?php

namespace Tests\Unit;

use App\Models\OrderShipment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class OrderShipmentTest extends TestCase
{
    public function test_order_shipment_matches_its_model_configuration(): void
    {
        $shipment = new OrderShipment;

        $this->assertContains(HasFactory::class, class_uses_recursive(OrderShipment::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(OrderShipment::class));
        $this->assertSame([
            'order_id',
            'status',
            'carrier',
            'tracking_number',
            'shipped_at',
            'delivered_at',
            'notes',
        ], $shipment->getFillable());
        $this->assertSame('datetime', $shipment->getCasts()['shipped_at']);
        $this->assertSame('datetime', $shipment->getCasts()['delivered_at']);
    }

    public function test_order_shipment_defines_its_order_relationship(): void
    {
        $order = (new OrderShipment)->order();

        $this->assertInstanceOf(BelongsTo::class, $order);
        $this->assertSame('order_id', $order->getForeignKeyName());
    }
}
