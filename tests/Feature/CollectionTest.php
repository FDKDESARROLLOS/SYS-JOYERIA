<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_paginated_collections_ordered_by_name(): void
    {
        $admin = User::factory()->create();

        Collection::create([
            'name' => 'Zafiros',
            'slug' => 'zafiros',
            'status' => true,
        ]);
        Collection::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.index'));

        $response
            ->assertOk()
            ->assertViewIs('collections.index')
            ->assertViewHas('collections', function ($collections): bool {
                return $collections instanceof LengthAwarePaginator
                    && $collections->pluck('name')->all() === ['Anillos', 'Zafiros'];
            });
    }

    public function test_collection_listing_searches_by_name(): void
    {
        $admin = User::factory()->create();
        Collection::create([
            'name' => 'Eternal',
            'slug' => 'eternal',
            'status' => true,
        ]);
        Collection::create([
            'name' => 'Bridal',
            'slug' => 'bridal',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.index', ['search' => 'Eternal']));

        $response->assertViewHas('collections', function ($collections): bool {
            return $collections->total() === 1
                && $collections->first()->name === 'Eternal';
        });
    }

    public function test_collection_listing_searches_by_slug(): void
    {
        $admin = User::factory()->create();
        Collection::create([
            'name' => 'Eternal',
            'slug' => 'eternal',
            'status' => true,
        ]);
        Collection::create([
            'name' => 'Bridal',
            'slug' => 'bridal',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.index', ['search' => 'bridal']));

        $response->assertViewHas('collections', function ($collections): bool {
            return $collections->total() === 1
                && $collections->first()->slug === 'bridal';
        });
    }

    public function test_collection_listing_filters_active_status(): void
    {
        $admin = User::factory()->create();
        Collection::create([
            'name' => 'Activa',
            'slug' => 'activa',
            'status' => true,
        ]);
        Collection::create([
            'name' => 'Inactiva',
            'slug' => 'inactiva',
            'status' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.index', ['status' => '1']));

        $response->assertViewHas('collections', function ($collections): bool {
            return $collections->total() === 1
                && $collections->first()->status === true;
        });
    }

    public function test_collection_listing_filters_inactive_status(): void
    {
        $admin = User::factory()->create();
        Collection::create([
            'name' => 'Activa',
            'slug' => 'activa',
            'status' => true,
        ]);
        Collection::create([
            'name' => 'Inactiva',
            'slug' => 'inactiva',
            'status' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.index', ['status' => '0']));

        $response->assertViewHas('collections', function ($collections): bool {
            return $collections->total() === 1
                && $collections->first()->status === false;
        });
    }

    public function test_collection_listing_paginates_ten_records_per_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Collection::create([
                'name' => "Colección {$index}",
                'slug' => "coleccion-{$index}",
                'status' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.index'));

        $response->assertViewHas('collections', function ($collections): bool {
            return $collections instanceof LengthAwarePaginator
                && $collections->total() === 11
                && $collections->perPage() === 10
                && $collections->currentPage() === 1
                && $collections->count() === 10;
        });
    }

    public function test_collection_listing_can_view_the_second_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Collection::create([
                'name' => "Colección {$index}",
                'slug' => "coleccion-{$index}",
                'status' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.index', ['page' => 2]));

        $response->assertViewHas('collections', function ($collections): bool {
            return $collections instanceof LengthAwarePaginator
                && $collections->currentPage() === 2
                && $collections->count() === 1;
        });
    }

    public function test_collection_filters_are_preserved_in_pagination_links(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Collection::create([
                'name' => "Filtrada {$index}",
                'slug' => "filtrada-{$index}",
                'status' => true,
            ]);
        }

        Collection::create([
            'name' => 'Otra colección',
            'slug' => 'otra-coleccion',
            'status' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.index', [
                'search' => 'Filtrada',
                'status' => '1',
            ]));

        $response
            ->assertOk()
            ->assertSee('search=Filtrada')
            ->assertSee('status=1')
            ->assertViewHas('collections', function ($collections): bool {
                return $collections instanceof LengthAwarePaginator
                    && $collections->total() === 11
                    && $collections->currentPage() === 1;
            });
    }

    public function test_authenticated_user_can_view_collection_creation_form(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.create'));

        $response
            ->assertOk()
            ->assertViewIs('collections.create')
            ->assertViewHas('collection', fn (Collection $collection): bool => $collection->status === true);
    }

    public function test_collection_is_created_with_an_automatic_slug(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('collections.store'), [
                'name' => 'Anillos de Oro',
                'status' => 1,
            ]);

        $response
            ->assertRedirect(route('collections.index'))
            ->assertSessionHas('status', 'Colección creada correctamente.');

        $this->assertDatabaseHas('collections', [
            'name' => 'Anillos de Oro',
            'slug' => 'anillos-de-oro',
            'status' => 1,
        ]);
    }

    public function test_duplicate_generated_slugs_receive_incremental_suffixes(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('collections.store'), [
            'name' => 'Anillos',
            'status' => 1,
        ]);
        $this->actingAs($admin)->post(route('collections.store'), [
            'name' => 'Anillos',
            'status' => 1,
        ]);
        $this->actingAs($admin)->post(route('collections.store'), [
            'name' => 'Anillos',
            'status' => 1,
        ]);

        $this->assertDatabaseHas('collections', ['slug' => 'anillos']);
        $this->assertDatabaseHas('collections', ['slug' => 'anillos-2']);
        $this->assertDatabaseHas('collections', ['slug' => 'anillos-3']);
    }

    public function test_collection_creation_validates_name_and_status(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('collections.store'), [
                'name' => '',
                'status' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['name', 'status']);
        $this->assertDatabaseCount('collections', 0);
    }

    public function test_authenticated_user_can_view_collection_edit_form(): void
    {
        $admin = User::factory()->create();
        $collection = Collection::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('collections.edit', $collection));

        $response
            ->assertOk()
            ->assertViewIs('collections.edit')
            ->assertViewHas('collection', $collection);
    }

    public function test_collection_update_regenerates_slug_when_name_changes(): void
    {
        $admin = User::factory()->create();
        $collection = Collection::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('collections.update', $collection), [
                'name' => 'Anillos de Oro',
                'status' => 0,
            ]);

        $response
            ->assertRedirect(route('collections.index'))
            ->assertSessionHas('status', 'Colección actualizada correctamente.');
        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'name' => 'Anillos de Oro',
            'slug' => 'anillos-de-oro',
            'status' => 0,
        ]);
    }

    public function test_collection_update_resolves_a_duplicate_generated_slug(): void
    {
        $admin = User::factory()->create();
        Collection::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
        ]);
        $collection = Collection::create([
            'name' => 'Collares',
            'slug' => 'collares',
            'status' => true,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('collections.update', $collection), [
                'name' => 'Anillos',
                'status' => 1,
            ])
            ->assertRedirect(route('collections.index'))
            ->assertSessionHas('status', 'Colección actualizada correctamente.');

        $this->assertDatabaseHas('collections', [
            'id' => $collection->id,
            'slug' => 'anillos-2',
        ]);
    }

    public function test_collection_is_soft_deleted(): void
    {
        $admin = User::factory()->create();
        $collection = Collection::create([
            'name' => 'Anillos',
            'slug' => 'anillos',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('collections.destroy', $collection));

        $response
            ->assertRedirect(route('collections.index'))
            ->assertSessionHas('status', 'Colección eliminada correctamente.');
        $this->assertSoftDeleted('collections', ['id' => $collection->id]);
    }
}
