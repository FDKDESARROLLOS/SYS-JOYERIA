<?php

namespace Tests\Unit;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_product_matches_its_model_configuration(): void
    {
        $product = new Product;

        $this->assertContains(HasFactory::class, class_uses_recursive(Product::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Product::class));
        $this->assertSame([
            'category_id',
            'collection_id',
            'base_size_id',
            'material_id',
            'name',
            'model',
            'code',
            'base_sku',
            'slug',
            'subtitle',
            'short_description',
            'karat',
            'metal_color',
            'price',
            'sale_price',
            'cost',
            'status',
            'featured',
            'is_new',
            'bestseller',
        ], $product->getFillable());
        $this->assertSame('integer', $product->getCasts()['karat']);
        $this->assertSame('decimal:2', $product->getCasts()['price']);
        $this->assertSame('decimal:2', $product->getCasts()['sale_price']);
        $this->assertSame('decimal:2', $product->getCasts()['cost']);
        $this->assertSame('boolean', $product->getCasts()['featured']);
        $this->assertSame('boolean', $product->getCasts()['is_new']);
        $this->assertSame('boolean', $product->getCasts()['bestseller']);
    }

    public function test_product_defines_its_relationships(): void
    {
        $product = new Product;

        $this->assertInstanceOf(BelongsTo::class, $product->category());
        $this->assertSame('category_id', $product->category()->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $product->collection());
        $this->assertSame('collection_id', $product->collection()->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $product->baseSize());
        $this->assertSame('base_size_id', $product->baseSize()->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $product->material());
        $this->assertSame('material_id', $product->material()->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $product->images());
        $this->assertSame('product_id', $product->images()->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $product->productStones());
        $this->assertSame('product_id', $product->productStones()->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $product->variants());
        $this->assertSame('product_id', $product->variants()->getForeignKeyName());
    }
}
