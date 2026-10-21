<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class SizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_sizes_index(): void
    {
        $admin = User::factory()->create();
        Size::create(['name' => '7', 'sort_order' => 0, 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.index'));

        $response
            ->assertOk()
            ->assertViewIs('sizes.index')
            ->assertViewHas('sizes');
    }

    public function test_authenticated_user_can_view_size_creation_form(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.create'));

        $response
            ->assertOk()
            ->assertViewIs('sizes.create')
            ->assertViewHas('size', fn (Size $size): bool => $size->status === true);
    }

    public function test_size_is_created_with_the_first_available_sort_order(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('sizes.store'), [
                'name' => '7',
                'status' => 1,
            ]);

        $response
            ->assertRedirect(route('sizes.index'))
            ->assertSessionHas('status', 'Talla creada correctamente.');

        $this->assertDatabaseHas('sizes', [
            'name' => '7',
            'sort_order' => 0,
            'status' => 1,
        ]);
    }

    public function test_size_can_be_created_with_one_category(): void
    {
        $admin = User::factory()->create();
        $category = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('sizes.store'), [
                'name' => '7',
                'status' => 1,
                'category_ids' => [$category->id],
            ]);

        $response->assertRedirect(route('sizes.index'));
        $size = Size::where('name', '7')->firstOrFail();

        $this->assertDatabaseHas('category_size', [
            'category_id' => $category->id,
            'size_id' => $size->id,
        ]);
    }

    public function test_size_can_be_created_with_multiple_categories(): void
    {
        $admin = User::factory()->create();
        $categories = collect([
            ['name' => 'Anillos', 'slug' => 'anillos'],
            ['name' => 'Collares', 'slug' => 'collares'],
        ])->map(fn (array $attributes): Category => Category::create([
            ...$attributes,
            'status' => true,
            'sort_order' => 1,
        ]));

        $this
            ->actingAs($admin)
            ->post(route('sizes.store'), [
                'name' => 'Medida universal',
                'status' => 1,
                'category_ids' => $categories->pluck('id')->all(),
            ])
            ->assertRedirect(route('sizes.index'));

        $size = Size::where('name', 'Medida universal')->firstOrFail();

        $this->assertCount(2, $size->categories);
        $this->assertDatabaseHas('category_size', [
            'category_id' => $categories->first()->id,
            'size_id' => $size->id,
        ]);
        $this->assertDatabaseHas('category_size', [
            'category_id' => $categories->last()->id,
            'size_id' => $size->id,
        ]);
    }

    public function test_second_size_receives_the_next_sort_order(): void
    {
        $admin = User::factory()->create();
        Size::create(['name' => '7', 'sort_order' => 4, 'status' => true]);

        $this
            ->actingAs($admin)
            ->post(route('sizes.store'), [
                'name' => '8',
                'status' => 1,
            ])
            ->assertRedirect(route('sizes.index'));

        $this->assertDatabaseHas('sizes', [
            'name' => '8',
            'sort_order' => 5,
        ]);
    }

    public function test_size_creation_validates_name_and_status(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('sizes.store'), [
                'name' => '',
                'status' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['name', 'status']);
        $this->assertDatabaseCount('sizes', 0);
    }

    public function test_authenticated_user_can_view_size_edit_form(): void
    {
        $admin = User::factory()->create();
        $size = Size::create(['name' => '7', 'sort_order' => 2, 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.edit', $size));

        $response
            ->assertOk()
            ->assertViewIs('sizes.edit')
            ->assertViewHas('size', $size);
    }

    public function test_size_edit_form_loads_selected_categories_and_only_active_categories(): void
    {
        $admin = User::factory()->create();
        $selectedCategory = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $subCategory = Category::create([
            'parent_id' => $selectedCategory->id,
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'status' => true,
            'sort_order' => 2,
        ]);
        $inactiveCategory = Category::create([
            'name' => 'Collares',
            'slug' => 'collares',
            'status' => false,
            'sort_order' => 3,
        ]);
        $size = Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);
        $size->categories()->attach([$selectedCategory->id, $subCategory->id]);

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.edit', $size));

        $response
            ->assertViewHas('selectedCategoryIds', [$selectedCategory->id])
            ->assertViewHas('categories', function ($categories) use ($selectedCategory, $inactiveCategory): bool {
                return $categories->contains($selectedCategory)
                    && ! $categories->contains($inactiveCategory);
            });
    }

    public function test_size_selector_shows_only_active_main_categories(): void
    {
        $admin = User::factory()->create();
        $mainCategory = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        Category::create([
            'parent_id' => $mainCategory->id,
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'status' => true,
            'sort_order' => 2,
        ]);
        Category::create([
            'name' => 'Collares',
            'slug' => 'collares',
            'status' => false,
            'sort_order' => 3,
        ]);
        $deletedCategory = Category::create([
            'name' => 'Pulseras',
            'slug' => 'pulseras',
            'status' => true,
            'sort_order' => 4,
        ]);
        $deletedCategory->delete();

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.create'));

        $response->assertViewHas('categories', function ($categories) use ($mainCategory): bool {
            return $categories->pluck('id')->all() === [$mainCategory->id];
        });
    }

    public function test_subcategory_inherits_sizes_from_its_main_category(): void
    {
        $mainCategory = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $subCategory = Category::create([
            'parent_id' => $mainCategory->id,
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'status' => true,
            'sort_order' => 2,
        ]);
        $size = Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);
        $size->categories()->attach($mainCategory);

        $applicableSizes = $subCategory->applicableSizes();

        $this->assertTrue($applicableSizes->contains('id', $size->id));
        $this->assertDatabaseCount('category_size', 1);
        $this->assertDatabaseMissing('category_size', [
            'category_id' => $subCategory->id,
            'size_id' => $size->id,
        ]);
    }

    public function test_size_update_preserves_sort_order(): void
    {
        $admin = User::factory()->create();
        $size = Size::create(['name' => '7', 'sort_order' => 6, 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('sizes.update', $size), [
                'name' => '7 ajustada',
                'status' => 0,
            ]);

        $response
            ->assertRedirect(route('sizes.index'))
            ->assertSessionHas('status', 'Talla actualizada correctamente.');

        $this->assertDatabaseHas('sizes', [
            'id' => $size->id,
            'name' => '7 ajustada',
            'sort_order' => 6,
            'status' => 0,
        ]);
    }

    public function test_size_update_syncs_category_associations(): void
    {
        $admin = User::factory()->create();
        $oldCategory = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $newCategory = Category::create([
            'name' => 'Collares',
            'slug' => 'collares',
            'status' => true,
            'sort_order' => 2,
        ]);
        $size = Size::create(['name' => '7', 'sort_order' => 2, 'status' => true]);
        $size->categories()->attach($oldCategory);

        $this
            ->actingAs($admin)
            ->patch(route('sizes.update', $size), [
                'name' => '7 actualizado',
                'status' => 1,
                'category_ids' => [$newCategory->id],
            ])
            ->assertRedirect(route('sizes.index'));

        $this->assertDatabaseMissing('category_size', [
            'category_id' => $oldCategory->id,
            'size_id' => $size->id,
        ]);
        $this->assertDatabaseHas('category_size', [
            'category_id' => $newCategory->id,
            'size_id' => $size->id,
        ]);
    }

    public function test_size_update_removes_legacy_subcategory_associations(): void
    {
        $admin = User::factory()->create();
        $mainCategory = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $subCategory = Category::create([
            'parent_id' => $mainCategory->id,
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'status' => true,
            'sort_order' => 2,
        ]);
        $size = Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);
        $size->categories()->attach($subCategory);

        $this
            ->actingAs($admin)
            ->patch(route('sizes.update', $size), [
                'name' => '7',
                'status' => 1,
                'category_ids' => [$mainCategory->id],
            ])
            ->assertRedirect(route('sizes.index'));

        $this->assertDatabaseHas('category_size', [
            'category_id' => $mainCategory->id,
            'size_id' => $size->id,
        ]);
        $this->assertDatabaseMissing('category_size', [
            'category_id' => $subCategory->id,
            'size_id' => $size->id,
        ]);
    }

    public function test_size_update_rejects_subcategory_ids(): void
    {
        $admin = User::factory()->create();
        $mainCategory = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $subCategory = Category::create([
            'parent_id' => $mainCategory->id,
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'status' => true,
            'sort_order' => 2,
        ]);
        $size = Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('sizes.update', $size), [
                'name' => '7',
                'status' => 1,
                'category_ids' => [$subCategory->id],
            ]);

        $response->assertSessionHasErrors(['category_ids.0']);
        $this->assertDatabaseMissing('category_size', [
            'category_id' => $subCategory->id,
            'size_id' => $size->id,
        ]);
    }

    public function test_size_update_can_remove_all_category_associations(): void
    {
        $admin = User::factory()->create();
        $category = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $size = Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);
        $size->categories()->attach($category);

        $this
            ->actingAs($admin)
            ->patch(route('sizes.update', $size), [
                'name' => '7',
                'status' => 1,
                'category_ids' => [],
            ])
            ->assertRedirect(route('sizes.index'));

        $this->assertDatabaseMissing('category_size', [
            'category_id' => $category->id,
            'size_id' => $size->id,
        ]);
    }

    public function test_size_category_ids_must_exist(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('sizes.store'), [
                'name' => '7',
                'status' => 1,
                'category_ids' => [999999],
            ]);

        $response->assertSessionHasErrors(['category_ids.0']);
        $this->assertDatabaseCount('sizes', 0);
    }

    public function test_size_listing_searches_by_name(): void
    {
        $admin = User::factory()->create();
        Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);
        Size::create(['name' => '18 cm', 'sort_order' => 2, 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.index', ['search' => '18']));

        $response->assertViewHas('sizes', function ($sizes): bool {
            return $sizes->total() === 1
                && $sizes->first()->name === '18 cm';
        });
    }

    public function test_size_listing_filters_active_status(): void
    {
        $admin = User::factory()->create();
        Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);
        Size::create(['name' => '8', 'sort_order' => 2, 'status' => false]);

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.index', ['status' => '1']));

        $response->assertViewHas('sizes', function ($sizes): bool {
            return $sizes->total() === 1
                && $sizes->first()->status === true;
        });
    }

    public function test_size_listing_filters_inactive_status(): void
    {
        $admin = User::factory()->create();
        Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);
        Size::create(['name' => '8', 'sort_order' => 2, 'status' => false]);

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.index', ['status' => '0']));

        $response->assertViewHas('sizes', function ($sizes): bool {
            return $sizes->total() === 1
                && $sizes->first()->status === false;
        });
    }

    public function test_size_listing_paginates_ten_records_per_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Size::create([
                'name' => "Talla {$index}",
                'sort_order' => $index,
                'status' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.index'));

        $response->assertViewHas('sizes', function ($sizes): bool {
            return $sizes instanceof LengthAwarePaginator
                && $sizes->total() === 11
                && $sizes->perPage() === 10
                && $sizes->currentPage() === 1
                && $sizes->count() === 10;
        });
    }

    public function test_size_listing_can_view_the_second_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Size::create([
                'name' => "Talla {$index}",
                'sort_order' => $index,
                'status' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.index', ['page' => 2]));

        $response->assertViewHas('sizes', function ($sizes): bool {
            return $sizes instanceof LengthAwarePaginator
                && $sizes->currentPage() === 2
                && $sizes->count() === 1;
        });
    }

    public function test_size_filters_are_preserved_in_pagination_links(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Size::create([
                'name' => "Filtrada {$index}",
                'sort_order' => $index,
                'status' => true,
            ]);
        }

        Size::create([
            'name' => 'Otra talla',
            'sort_order' => 12,
            'status' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.index', [
                'search' => 'Filtrada',
                'status' => '1',
            ]));

        $response
            ->assertOk()
            ->assertSee('search=Filtrada')
            ->assertSee('status=1')
            ->assertViewHas('sizes', function ($sizes): bool {
                return $sizes instanceof LengthAwarePaginator
                    && $sizes->total() === 11
                    && $sizes->currentPage() === 1;
            });
    }

    public function test_size_is_soft_deleted(): void
    {
        $admin = User::factory()->create();
        $size = Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('sizes.destroy', $size));

        $response
            ->assertRedirect(route('sizes.index'))
            ->assertSessionHas('status', 'Talla eliminada correctamente.');

        $this->assertSoftDeleted('sizes', ['id' => $size->id]);
        $this->assertNotNull(Size::withTrashed()->find($size->id));
    }

    public function test_size_index_eager_loads_categories_to_avoid_n_plus_one_queries(): void
    {
        $admin = User::factory()->create();
        $category = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $size = Size::create(['name' => '7', 'sort_order' => 1, 'status' => true]);
        $size->categories()->attach($category);

        $response = $this
            ->actingAs($admin)
            ->get(route('sizes.index'));

        $response->assertViewHas('sizes', function ($sizes): bool {
            return $sizes->every(fn (Size $size): bool => $size->relationLoaded('categories'));
        });
    }
}
