<?php

namespace Tests\Unit;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    public function test_collection_matches_its_model_configuration(): void
    {
        $collection = new Collection;

        $this->assertContains(HasFactory::class, class_uses_recursive(Collection::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Collection::class));
        $this->assertSame([
            'name',
            'slug',
            'status',
        ], $collection->getFillable());
        $this->assertSame('boolean', $collection->getCasts()['status']);
    }

    public function test_collection_defines_its_products_relationship(): void
    {
        $products = (new Collection)->products();

        $this->assertInstanceOf(HasMany::class, $products);
        $this->assertSame('collection_id', $products->getForeignKeyName());
    }
}
