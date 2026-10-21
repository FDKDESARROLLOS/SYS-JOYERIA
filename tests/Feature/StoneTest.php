<?php

namespace Tests\Feature;

use App\Models\Stone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class StoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_stones_index(): void
    {
        $admin = User::factory()->create();
        Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.index'));

        $response
            ->assertOk()
            ->assertViewIs('stones.index')
            ->assertViewHas('stones');
    }

    public function test_authenticated_user_can_view_stone_creation_form(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.create'));

        $response
            ->assertOk()
            ->assertViewIs('stones.create')
            ->assertViewHas('stone', fn (Stone $stone): bool => $stone->status === true);
    }

    public function test_stone_is_created_with_an_automatic_slug(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('stones.store'), [
                'name' => 'Piedra lunar',
                'status' => 1,
            ]);

        $response
            ->assertRedirect(route('stones.index'))
            ->assertSessionHas('status', 'Piedra creada correctamente.');

        $this->assertDatabaseHas('stones', [
            'name' => 'Piedra lunar',
            'slug' => 'piedra-lunar',
            'status' => 1,
        ]);
    }

    public function test_stone_creation_validates_name_and_status(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('stones.store'), [
                'name' => '',
                'status' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['name', 'status']);
        $this->assertDatabaseCount('stones', 0);
    }

    public function test_duplicate_generated_slugs_receive_incremental_suffixes(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('stones.store'), ['name' => 'Diamante', 'status' => 1]);
        $this->actingAs($admin)->post(route('stones.store'), ['name' => 'Diamante', 'status' => 1]);
        $this->actingAs($admin)->post(route('stones.store'), ['name' => 'Diamante', 'status' => 1]);

        $this->assertDatabaseHas('stones', ['slug' => 'diamante']);
        $this->assertDatabaseHas('stones', ['slug' => 'diamante-2']);
        $this->assertDatabaseHas('stones', ['slug' => 'diamante-3']);
    }

    public function test_authenticated_user_can_view_stone_edit_form(): void
    {
        $admin = User::factory()->create();
        $stone = Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.edit', $stone));

        $response
            ->assertOk()
            ->assertViewIs('stones.edit')
            ->assertViewHas('stone', $stone);
    }

    public function test_stone_can_be_updated_and_slug_is_regenerated_when_name_changes(): void
    {
        $admin = User::factory()->create();
        $stone = Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('stones.update', $stone), [
                'name' => 'Diamante negro',
                'status' => 0,
            ]);

        $response
            ->assertRedirect(route('stones.index'))
            ->assertSessionHas('status', 'Piedra actualizada correctamente.');

        $this->assertDatabaseHas('stones', [
            'id' => $stone->id,
            'name' => 'Diamante negro',
            'slug' => 'diamante-negro',
            'status' => 0,
        ]);
    }

    public function test_stone_listing_searches_by_name(): void
    {
        $admin = User::factory()->create();
        Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);
        Stone::create(['name' => 'Rubí', 'slug' => 'rubi', 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.index', ['search' => 'Diamante']));

        $response->assertViewHas('stones', function ($stones): bool {
            return $stones->total() === 1
                && $stones->first()->name === 'Diamante';
        });
    }

    public function test_stone_listing_searches_by_slug(): void
    {
        $admin = User::factory()->create();
        Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);
        Stone::create(['name' => 'Rubí', 'slug' => 'rubi', 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.index', ['search' => 'rubi']));

        $response->assertViewHas('stones', function ($stones): bool {
            return $stones->total() === 1
                && $stones->first()->slug === 'rubi';
        });
    }

    public function test_stone_listing_filters_active_status(): void
    {
        $admin = User::factory()->create();
        Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);
        Stone::create(['name' => 'Rubí', 'slug' => 'rubi', 'status' => false]);

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.index', ['status' => '1']));

        $response->assertViewHas('stones', function ($stones): bool {
            return $stones->total() === 1
                && $stones->first()->status === true;
        });
    }

    public function test_stone_listing_filters_inactive_status(): void
    {
        $admin = User::factory()->create();
        Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);
        Stone::create(['name' => 'Rubí', 'slug' => 'rubi', 'status' => false]);

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.index', ['status' => '0']));

        $response->assertViewHas('stones', function ($stones): bool {
            return $stones->total() === 1
                && $stones->first()->status === false;
        });
    }

    public function test_stone_listing_paginates_ten_records_per_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Stone::create([
                'name' => "Piedra {$index}",
                'slug' => "piedra-{$index}",
                'status' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.index'));

        $response->assertViewHas('stones', function ($stones): bool {
            return $stones instanceof LengthAwarePaginator
                && $stones->total() === 11
                && $stones->perPage() === 10
                && $stones->currentPage() === 1
                && $stones->count() === 10;
        });
    }

    public function test_stone_listing_can_view_the_second_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Stone::create([
                'name' => "Piedra {$index}",
                'slug' => "piedra-{$index}",
                'status' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.index', ['page' => 2]));

        $response->assertViewHas('stones', function ($stones): bool {
            return $stones instanceof LengthAwarePaginator
                && $stones->currentPage() === 2
                && $stones->count() === 1;
        });
    }

    public function test_stone_filters_are_preserved_in_pagination_links(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Stone::create([
                'name' => "Filtrada {$index}",
                'slug' => "filtrada-{$index}",
                'status' => true,
            ]);
        }

        Stone::create([
            'name' => 'Otra piedra',
            'slug' => 'otra-piedra',
            'status' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('stones.index', [
                'search' => 'Filtrada',
                'status' => '1',
            ]));

        $response
            ->assertOk()
            ->assertSee('search=Filtrada')
            ->assertSee('status=1')
            ->assertViewHas('stones', function ($stones): bool {
                return $stones instanceof LengthAwarePaginator
                    && $stones->total() === 11
                    && $stones->currentPage() === 1;
            });
    }

    public function test_stone_is_soft_deleted(): void
    {
        $admin = User::factory()->create();
        $stone = Stone::create(['name' => 'Diamante', 'slug' => 'diamante', 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('stones.destroy', $stone));

        $response
            ->assertRedirect(route('stones.index'))
            ->assertSessionHas('status', 'Piedra eliminada correctamente.');

        $this->assertSoftDeleted('stones', ['id' => $stone->id]);
        $this->assertNotNull(Stone::withTrashed()->find($stone->id));
    }
}
