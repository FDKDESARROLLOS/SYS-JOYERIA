<?php

namespace Tests\Unit;

use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class SizeTest extends TestCase
{
    public function test_size_matches_its_model_configuration(): void
    {
        $size = new Size;

        $this->assertContains(HasFactory::class, class_uses_recursive(Size::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Size::class));
        $this->assertSame([
            'name',
            'sort_order',
            'status',
        ], $size->getFillable());
        $this->assertSame('integer', $size->getCasts()['sort_order']);
        $this->assertSame('boolean', $size->getCasts()['status']);
    }

    public function test_size_defines_its_relationships(): void
    {
        $size = new Size;

        $categories = $size->categories();
        $products = $size->products();
        $variants = $size->variants();

        $this->assertInstanceOf(BelongsToMany::class, $categories);
        $this->assertSame('category_size', $categories->getTable());
        $this->assertTrue($categories->withTimestamps);

        $this->assertInstanceOf(HasMany::class, $products);
        $this->assertSame('base_size_id', $products->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $variants);
        $this->assertSame('size_id', $variants->getForeignKeyName());
    }
}
