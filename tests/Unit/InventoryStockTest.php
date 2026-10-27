<?php

namespace Tests\Unit;

use App\Models\InventoryStock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class InventoryStockTest extends TestCase
{
    public function test_inventory_stock_matches_its_model_configuration(): void
    {
        $inventoryStock = new InventoryStock;

        $this->assertContains(HasFactory::class, class_uses_recursive(InventoryStock::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(InventoryStock::class));
        $this->assertSame([
            'product_variant_id',
            'current_stock',
            'reserved_stock',
            'minimum_stock',
        ], $inventoryStock->getFillable());
        $this->assertSame('integer', $inventoryStock->getCasts()['current_stock']);
        $this->assertSame('integer', $inventoryStock->getCasts()['reserved_stock']);
        $this->assertSame('integer', $inventoryStock->getCasts()['minimum_stock']);
    }

    public function test_available_stock_never_goes_below_zero(): void
    {
        $inventoryStock = new InventoryStock([
            'current_stock' => 5,
            'reserved_stock' => 4,
        ]);

        $this->assertSame(1, $inventoryStock->available_stock);

        $inventoryStock->reserved_stock = 6;

        $this->assertSame(0, $inventoryStock->available_stock);
    }

    public function test_inventory_stock_defines_its_product_variant_relationship(): void
    {
        $productVariant = (new InventoryStock)->productVariant();

        $this->assertInstanceOf(BelongsTo::class, $productVariant);
        $this->assertSame('product_variant_id', $productVariant->getForeignKeyName());
    }

    public function test_inventory_stock_defines_its_movements_relationship(): void
    {
        $movements = (new InventoryStock)->movements();

        $this->assertInstanceOf(HasMany::class, $movements);
        $this->assertSame('inventory_stock_id', $movements->getForeignKeyName());
    }

    public function test_inventory_stock_defines_its_reservations_relationship(): void
    {
        $reservations = (new InventoryStock)->reservations();

        $this->assertInstanceOf(HasMany::class, $reservations);
        $this->assertSame('inventory_stock_id', $reservations->getForeignKeyName());
    }
}
