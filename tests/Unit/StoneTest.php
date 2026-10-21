<?php

namespace Tests\Unit;

use App\Models\Stone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class StoneTest extends TestCase
{
    public function test_stone_matches_its_model_configuration(): void
    {
        $stone = new Stone;

        $this->assertContains(HasFactory::class, class_uses_recursive(Stone::class));
        $this->assertContains(SoftDeletes::class, class_uses_recursive(Stone::class));
        $this->assertSame([
            'name',
            'slug',
            'status',
        ], $stone->getFillable());
        $this->assertSame('boolean', $stone->getCasts()['status']);
    }

    public function test_stone_defines_its_relationships(): void
    {
        $stone = new Stone;

        $productStones = $stone->productStones();
        $variants = $stone->variants();

        $this->assertInstanceOf(HasMany::class, $productStones);
        $this->assertSame('stone_id', $productStones->getForeignKeyName());

        $this->assertInstanceOf(HasMany::class, $variants);
        $this->assertSame('stone_id', $variants->getForeignKeyName());
    }
}
