<?php

namespace Tests\Unit;

use App\Models\ProductStone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class ProductStoneTest extends TestCase
{
    public function test_product_stone_matches_its_model_configuration(): void
    {
        $productStone = new ProductStone;

        $this->assertContains(HasFactory::class, class_uses_recursive(ProductStone::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(ProductStone::class));
        $this->assertSame([
            'product_id',
            'stone_id',
            'position',
            'sort_order',
            'shape',
            'carats',
            'quantity',
            'stones_per_side',
            'origin',
            'certification',
            'color',
            'clarity',
            'cut',
        ], $productStone->getFillable());
        $this->assertSame('decimal:2', $productStone->getCasts()['carats']);
        $this->assertSame('integer', $productStone->getCasts()['quantity']);
        $this->assertSame('integer', $productStone->getCasts()['stones_per_side']);
    }

    public function test_product_stone_defines_its_product_and_stone_relationships(): void
    {
        $productStone = new ProductStone;

        $product = $productStone->product();
        $stone = $productStone->stone();

        $this->assertInstanceOf(BelongsTo::class, $product);
        $this->assertSame('product_id', $product->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $stone);
        $this->assertSame('stone_id', $stone->getForeignKeyName());
    }
}
