<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_paginated_categories_ordered_by_sort_order_and_name(): void
    {
        $admin = User::factory()->create();

        Category::create([
            'name' => 'Zafiros',
            'slug' => 'zafiros',
            'status' => true,
            'sort_order' => 2,
        ]);
        Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        Category::create([
            'name' => 'Aretes',
            'slug' => 'aretes',
            'status' => true,
            'sort_order' => 1,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('categories.index'));

        $response
            ->assertOk()
            ->assertViewIs('categories.index')
            ->assertViewHas('categories', function ($categories): bool {
                return $categories instanceof LengthAwarePaginator
                    && $categories->pluck('name')->all() === ['Anillos', 'Aretes', 'Zafiros'];
            });
    }

    public function test_category_listing_paginates_twenty_records_per_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Category::create([
                'name' => "Categoría {$index}",
                'slug' => "categoria-{$index}",
                'status' => true,
                'sort_order' => $index,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('categories.index'));

        $response->assertViewHas('categories', function ($categories): bool {
            return $categories instanceof LengthAwarePaginator
                && $categories->total() === 11
                && $categories->perPage() === 10
                && $categories->currentPage() === 1
                && $categories->count() === 10;
        });
    }

    public function test_category_listing_can_view_the_second_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Category::create([
                'name' => "Categoría {$index}",
                'slug' => "categoria-{$index}",
                'status' => true,
                'sort_order' => $index,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('categories.index', ['page' => 2]));

        $response
            ->assertOk()
            ->assertViewHas('categories', function ($categories): bool {
                return $categories instanceof LengthAwarePaginator
                    && $categories->currentPage() === 2
                    && $categories->count() === 1;
            });
    }

    public function test_category_filters_are_preserved_in_pagination_links(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Category::create([
                'name' => "Filtrada {$index}",
                'slug' => "filtrada-{$index}",
                'status' => true,
                'sort_order' => $index,
            ]);
        }

        Category::create([
            'name' => 'Otra categoría',
            'slug' => 'otra-categoria',
            'status' => false,
            'sort_order' => 12,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('categories.index', [
                'search' => 'Filtrada',
                'status' => '1',
            ]));

        $response
            ->assertOk()
            ->assertSee('search=Filtrada')
            ->assertSee('status=1')
            ->assertViewHas('categories', function ($categories): bool {
                return $categories instanceof LengthAwarePaginator
                    && $categories->total() === 11
                    && $categories->currentPage() === 1;
            });
    }

    public function test_authenticated_user_can_view_category_creation_form(): void
    {
        $admin = User::factory()->create();
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('categories.create'));

        $response
            ->assertOk()
            ->assertViewIs('categories.create')
            ->assertViewHas('parentCategories', fn ($categories) => $categories->contains($parent));
    }

    public function test_category_is_created_with_an_automatic_slug_and_nullable_parent(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => 'Anillos',
                'status' => 1,
                'parent_id' => null,
            ]);

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status', 'Categoría creada correctamente.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Anillos',
            'slug' => 'anillos',
            'parent_id' => null,
            'status' => 1,
            'sort_order' => 0,
        ]);
    }

    public function test_category_is_assigned_the_next_available_sort_order(): void
    {
        $admin = User::factory()->create();
        Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 4,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => 'Collares',
                'status' => 1,
                'parent_id' => null,
            ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Collares',
            'sort_order' => 5,
        ]);
    }

    public function test_category_creation_validates_fields_and_parent_id(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => '',
                'status' => 'invalid',
                'parent_id' => 999999,
            ]);

        $response->assertSessionHasErrors([
            'name',
            'status',
            'parent_id',
        ]);
    }

    public function test_authenticated_user_can_view_category_edit_form(): void
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
            ->get(route('categories.edit', $category));

        $response
            ->assertOk()
            ->assertViewIs('categories.edit')
            ->assertViewHas('category', $category);
    }

    public function test_category_update_regenerates_slug_when_name_changes(): void
    {
        $admin = User::factory()->create();
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $category = Category::create([
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'status' => true,
            'sort_order' => 2,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('categories.update', $category), [
                'name' => 'Solitarios',
                'status' => 0,
                'parent_id' => $parent->id,
            ]);

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status', 'Categoría actualizada correctamente.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => 0,
            'sort_order' => 2,
            'parent_id' => $parent->id,
        ]);
    }

    public function test_category_update_rejects_self_parent(): void
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
            ->patch(route('categories.update', $category), [
                'name' => 'Anillos actualizados',
                'status' => 1,
                'parent_id' => $category->id,
            ]);

        $response->assertSessionHasErrors(['parent_id']);
    }

    public function test_category_cannot_use_a_subcategory_as_its_parent(): void
    {
        $admin = User::factory()->create();
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => true,
            'sort_order' => 2,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => 'Clásicos',
                'status' => 1,
                'parent_id' => $child->id,
            ]);

        $response->assertSessionHasErrors(['parent_id']);
        $this->assertDatabaseMissing('categories', ['slug' => 'clasicos']);
    }

    public function test_duplicate_generated_slugs_receive_incremental_suffixes(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Anillos',
            'status' => 1,
        ]);
        $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Anillos',
            'status' => 1,
        ]);
        $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Anillos',
            'status' => 1,
        ]);

        $this->assertDatabaseHas('categories', ['slug' => 'anillos']);
        $this->assertDatabaseHas('categories', ['slug' => 'anillos-2']);
        $this->assertDatabaseHas('categories', ['slug' => 'anillos-3']);
    }

    public function test_category_is_soft_deleted(): void
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
            ->delete(route('categories.destroy', $category));

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status', 'Categoría eliminada correctamente.');
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_deleting_parent_category_soft_deletes_all_descendants_and_excludes_them_from_normal_views(): void
    {
        $admin = User::factory()->create();
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => true,
            'sort_order' => 2,
        ]);
        $grandchild = Category::create([
            'parent_id' => $child->id,
            'name' => 'Solitarios clásicos',
            'slug' => 'solitarios-clasicos',
            'status' => true,
            'sort_order' => 3,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('categories.destroy', $parent));

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status', 'Categoría eliminada correctamente.');

        $this->assertSoftDeleted('categories', ['id' => $parent->id]);
        $this->assertSoftDeleted('categories', ['id' => $child->id]);
        $this->assertSoftDeleted('categories', ['id' => $grandchild->id]);
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => $parent->id,
            'status' => true,
        ]);
        $this->assertDatabaseHas('categories', [
            'id' => $grandchild->id,
            'parent_id' => $child->id,
            'status' => true,
        ]);

        $this->assertNotNull(Category::withTrashed()->find($parent->id));
        $this->assertNotNull(Category::withTrashed()->find($child->id));
        $this->assertNotNull(Category::withTrashed()->find($grandchild->id));

        $child->refresh();
        $this->assertFalse($child->isEffectivelyActive());

        $createResponse = $this
            ->actingAs($admin)
            ->get(route('categories.create'));

        $createResponse->assertViewHas('parentCategories', function ($parentCategories) use ($parent, $child): bool {
            return ! $parentCategories->contains('id', $parent->id)
                && ! $parentCategories->contains('id', $child->id);
        });

        $indexResponse = $this
            ->actingAs($admin)
            ->get(route('categories.index'));

        $indexResponse
            ->assertOk()
            ->assertViewHas('categories', fn ($categories): bool => $categories->isEmpty());
    }

    public function test_deleting_child_category_does_not_delete_parent_or_siblings(): void
    {
        $admin = User::factory()->create();
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => true,
            'sort_order' => 2,
        ]);
        $sibling = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Compromiso',
            'slug' => 'compromiso',
            'status' => true,
            'sort_order' => 3,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('categories.destroy', $child));

        $response->assertRedirect(route('categories.index'));
        $this->assertSoftDeleted('categories', ['id' => $child->id]);
        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('categories', [
            'id' => $sibling->id,
            'parent_id' => $parent->id,
            'deleted_at' => null,
        ]);
        $this->assertNotNull(Category::withTrashed()->find($child->id));
    }

    public function test_active_parent_category_is_effectively_active(): void
    {
        $category = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($category->isEffectivelyActive());
    }

    public function test_inactive_parent_category_is_not_effectively_active(): void
    {
        $category = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => false,
            'sort_order' => 1,
        ]);

        $this->assertFalse($category->isEffectivelyActive());
    }

    public function test_active_child_with_active_parent_is_effectively_active(): void
    {
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => true,
            'sort_order' => 2,
        ]);

        $this->assertTrue($child->isEffectivelyActive());
    }

    public function test_active_child_with_inactive_parent_is_not_effectively_active(): void
    {
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => false,
            'sort_order' => 1,
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => true,
            'sort_order' => 2,
        ]);

        $this->assertFalse($child->isEffectivelyActive());
    }

    public function test_inactive_child_with_active_parent_is_not_effectively_active(): void
    {
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => false,
            'sort_order' => 2,
        ]);

        $this->assertFalse($child->isEffectivelyActive());
    }

    public function test_active_child_becomes_effectively_active_when_its_parent_is_reactivated(): void
    {
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => false,
            'sort_order' => 1,
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => true,
            'sort_order' => 2,
        ]);

        $this->assertFalse($child->isEffectivelyActive());

        $parent->update(['status' => true]);
        $child->refresh();

        $this->assertTrue($child->status);
        $this->assertTrue($child->isEffectivelyActive());
    }

    public function test_active_child_with_deleted_parent_is_not_effectively_active(): void
    {
        $parent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $child = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => true,
            'sort_order' => 2,
        ]);

        $parent->delete();
        $child->refresh();

        $this->assertTrue($child->status);
        $this->assertFalse($child->isEffectivelyActive());
    }

    public function test_category_listing_displays_effective_status_labels(): void
    {
        $admin = User::factory()->create();
        $activeParent = Category::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
            'sort_order' => 1,
        ]);
        $inactiveParent = Category::create([
            'name' => 'Collares',
            'slug' => 'collares',
            'status' => false,
            'sort_order' => 2,
        ]);
        Category::create([
            'parent_id' => $activeParent->id,
            'name' => 'Solitarios',
            'slug' => 'solitarios',
            'status' => true,
            'sort_order' => 3,
        ]);
        Category::create([
            'parent_id' => $inactiveParent->id,
            'name' => 'Gargantillas',
            'slug' => 'gargantillas',
            'status' => true,
            'sort_order' => 4,
        ]);
        Category::create([
            'name' => 'Pulseras',
            'slug' => 'pulseras',
            'status' => false,
            'sort_order' => 5,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('categories.index'));

        $response
            ->assertOk()
            ->assertSee('Activo')
            ->assertSee('Inactivo')
            ->assertSee('Inactivo por categoría padre');
    }
}
