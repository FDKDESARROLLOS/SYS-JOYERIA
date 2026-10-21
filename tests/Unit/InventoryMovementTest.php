<?php

namespace Tests\Unit;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_movement_matches_its_model_configuration(): void
    {
        $movement = new InventoryMovement;

        $this->assertContains(HasFactory::class, class_uses_recursive(InventoryMovement::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(InventoryMovement::class));
        $this->assertSame([
            'inventory_stock_id',
            'user_id',
            'type',
            'quantity',
            'previous_stock',
            'resulting_stock',
            'reason',
            'reference',
            'movement_at',
        ], $movement->getFillable());
        $this->assertSame('integer', $movement->getCasts()['quantity']);
        $this->assertSame('integer', $movement->getCasts()['previous_stock']);
        $this->assertSame('integer', $movement->getCasts()['resulting_stock']);
        $this->assertSame('datetime', $movement->getCasts()['movement_at']);
    }

    public function test_inventory_movement_defines_its_relationships(): void
    {
        $movement = new InventoryMovement;

        $inventoryStock = $movement->inventoryStock();
        $user = $movement->user();

        $this->assertInstanceOf(BelongsTo::class, $inventoryStock);
        $this->assertSame('inventory_stock_id', $inventoryStock->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $user);
        $this->assertSame('user_id', $user->getForeignKeyName());
    }

    public function test_user_id_is_nullable_in_the_database(): void
    {
        $columns = collect(Schema::getColumns('inventory_movements'))
            ->keyBy('name');

        $this->assertTrue($columns['user_id']['nullable']);
    }
}
