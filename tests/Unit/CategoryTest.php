<?php

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function test_category_matches_its_model_configuration(): void
    {
        $category = new Category;

        $this->assertContains(HasFactory::class, class_uses_recursive(Category::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Category::class));
        $this->assertSame([
            'parent_id',
            'name',
            'slug',
            'status',
            'sort_order',
        ], $category->getFillable());
        $this->assertSame('boolean', $category->getCasts()['status']);
        $this->assertSame('integer', $category->getCasts()['sort_order']);
    }

    public function test_category_defines_its_relationships(): void
    {
        $category = new Category;

        $this->assertInstanceOf(BelongsTo::class, $category->parent());
        $this->assertInstanceOf(HasMany::class, $category->children());
        $this->assertInstanceOf(HasMany::class, $category->products());
        $this->assertInstanceOf(BelongsToMany::class, $category->sizes());
        $this->assertSame('category_size', $category->sizes()->getTable());
        $this->assertTrue($category->sizes()->withTimestamps);
    }
}
