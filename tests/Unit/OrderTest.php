<?php

namespace Tests\Unit;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class OrderTest extends TestCase
{
    public function test_order_matches_its_model_configuration(): void
    {
        $order = new Order;

        $this->assertContains(HasFactory::class, class_uses_recursive(Order::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(Order::class));
        $this->assertSame([
            'order_number',
            'customer_name',
            'customer_email',
            'customer_phone',
            'shipping_address',
            'shipping_city',
            'shipping_state',
            'shipping_postal_code',
            'shipping_country',
            'subtotal',
            'shipping_cost',
            'iva',
            'total',
            'currency',
            'order_status',
            'notes',
            'placed_at',
        ], $order->getFillable());
        $this->assertSame('decimal:2', $order->getCasts()['subtotal']);
        $this->assertSame('decimal:2', $order->getCasts()['shipping_cost']);
        $this->assertSame('decimal:2', $order->getCasts()['iva']);
        $this->assertSame('decimal:2', $order->getCasts()['total']);
        $this->assertSame('datetime', $order->getCasts()['placed_at']);
        $this->assertNotContains('customer_id', $order->getFillable());
        $this->assertNotContains('payment_status', $order->getFillable());
    }

    public function test_order_defines_its_related_records(): void
    {
        $order = new Order;

        $items = $order->items();
        $payments = $order->payments();
        $shipments = $order->shipments();

        $this->assertInstanceOf(HasMany::class, $items);
        $this->assertSame('order_id', $items->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $payments);
        $this->assertSame('order_id', $payments->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $shipments);
        $this->assertSame('order_id', $shipments->getForeignKeyName());
    }
}
