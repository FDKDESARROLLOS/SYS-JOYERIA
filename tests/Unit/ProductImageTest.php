<?php

namespace Tests\Unit;

use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    public function test_product_image_matches_its_model_configuration(): void
    {
        $productImage = new ProductImage;

        $this->assertContains(HasFactory::class, class_uses_recursive(ProductImage::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(ProductImage::class));
        $this->assertSame([
            'product_id',
            'path',
            'alt',
            'sort_order',
            'is_cover',
        ], $productImage->getFillable());
        $this->assertSame('integer', $productImage->getCasts()['sort_order']);
        $this->assertSame('boolean', $productImage->getCasts()['is_cover']);
    }

    public function test_product_image_defines_its_product_relationship(): void
    {
        $product = (new ProductImage)->product();

        $this->assertInstanceOf(BelongsTo::class, $product);
        $this->assertSame('product_id', $product->getForeignKeyName());
    }
}
