<?php

namespace Tests\Unit;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_item_matches_its_model_configuration(): void
    {
        $orderItem = new OrderItem;

        $this->assertContains(HasFactory::class, class_uses_recursive(OrderItem::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(OrderItem::class));
        $this->assertSame([
            'order_id',
            'product_variant_id',
            'product_name_snapshot',
            'sku_snapshot',
            'variant_label_snapshot',
            'selected_material',
            'selected_karat',
            'selected_metal_color',
            'selected_stone_name',
            'selected_stone_carats',
            'selected_size_name',
            'unit_price',
            'quantity',
            'subtotal',
        ], $orderItem->getFillable());
        $this->assertSame('integer', $orderItem->getCasts()['selected_karat']);
        $this->assertSame('decimal:2', $orderItem->getCasts()['selected_stone_carats']);
        $this->assertSame('decimal:2', $orderItem->getCasts()['unit_price']);
        $this->assertSame('integer', $orderItem->getCasts()['quantity']);
        $this->assertSame('decimal:2', $orderItem->getCasts()['subtotal']);
    }

    public function test_order_item_defines_its_order_and_variant_relationships(): void
    {
        $orderItem = new OrderItem;

        $order = $orderItem->order();
        $productVariant = $orderItem->productVariant();

        $this->assertInstanceOf(BelongsTo::class, $order);
        $this->assertSame('order_id', $order->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $productVariant);
        $this->assertSame('product_variant_id', $productVariant->getForeignKeyName());
    }

    public function test_order_item_has_no_product_id_in_the_model_or_database(): void
    {
        $orderItem = new OrderItem;
        $columns = collect(Schema::getColumns('order_items'))
            ->pluck('name')
            ->all();

        $this->assertNotContains('product_id', $orderItem->getFillable());
        $this->assertNotContains('product_id', $columns);
    }
}
