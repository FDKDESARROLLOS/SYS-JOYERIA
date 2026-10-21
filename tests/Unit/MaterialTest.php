<?php

namespace Tests\Unit;

use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    public function test_material_matches_its_model_configuration(): void
    {
        $material = new Material;

        $this->assertContains(HasFactory::class, class_uses_recursive(Material::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Material::class));
        $this->assertSame([
            'name',
            'slug',
            'status',
        ], $material->getFillable());
        $this->assertSame('boolean', $material->getCasts()['status']);
    }

    public function test_material_defines_product_relationships(): void
    {
        $material = new Material;

        $products = $material->products();
        $variants = $material->variants();

        $this->assertInstanceOf(HasMany::class, $products);
        $this->assertSame('material_id', $products->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $variants);
        $this->assertSame('material_id', $variants->getForeignKeyName());
    }
}
