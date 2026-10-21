<?php

namespace Tests\Unit;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    public function test_product_variant_matches_its_model_configuration(): void
    {
        $variant = new ProductVariant;

        $this->assertContains(HasFactory::class, class_uses_recursive(ProductVariant::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(ProductVariant::class));
        $this->assertSame([
            'product_id',
            'size_id',
            'material_id',
            'stone_id',
            'sku',
            'karat',
            'metal_color',
            'cost',
            'price',
            'sale_price',
            'status',
        ], $variant->getFillable());
        $this->assertSame('integer', $variant->getCasts()['karat']);
        $this->assertSame('decimal:2', $variant->getCasts()['cost']);
        $this->assertSame('decimal:2', $variant->getCasts()['price']);
        $this->assertSame('decimal:2', $variant->getCasts()['sale_price']);
    }

    public function test_product_variant_defines_its_belongs_to_relationships(): void
    {
        $variant = new ProductVariant;

        $product = $variant->product();
        $size = $variant->size();
        $material = $variant->material();
        $stone = $variant->stone();

        $this->assertInstanceOf(BelongsTo::class, $product);
        $this->assertSame('product_id', $product->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $size);
        $this->assertSame('size_id', $size->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $material);
        $this->assertSame('material_id', $material->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $stone);
        $this->assertSame('stone_id', $stone->getForeignKeyName());
    }

    public function test_product_variant_defines_its_inventory_and_order_relationships(): void
    {
        $variant = new ProductVariant;

        $inventoryStock = $variant->inventoryStock();
        $orderItems = $variant->orderItems();

        $this->assertInstanceOf(HasOne::class, $inventoryStock);
        $this->assertSame('product_variant_id', $inventoryStock->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $orderItems);
        $this->assertSame('product_variant_id', $orderItems->getForeignKeyName());
    }
}
