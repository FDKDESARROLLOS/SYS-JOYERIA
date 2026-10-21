<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_materials_index(): void
    {
        $admin = User::factory()->create();
        Material::create([
            'name' => 'Oro',
            'slug' => 'oro',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.index'));

        $response
            ->assertOk()
            ->assertViewIs('materials.index')
            ->assertViewHas('materials');
    }

    public function test_authenticated_user_can_view_material_creation_form(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.create'));

        $response
            ->assertOk()
            ->assertViewIs('materials.create')
            ->assertViewHas('material', fn (Material $material): bool => $material->status === true);
    }

    public function test_material_is_created_with_an_automatic_slug(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('materials.store'), [
                'name' => 'Acero inoxidable',
                'status' => 1,
            ]);

        $response
            ->assertRedirect(route('materials.index'))
            ->assertSessionHas('status', 'Material creado correctamente.');

        $this->assertDatabaseHas('materials', [
            'name' => 'Acero inoxidable',
            'slug' => 'acero-inoxidable',
            'status' => 1,
        ]);
    }

    public function test_material_creation_validates_name_and_status(): void
    {
        $admin = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('materials.store'), [
                'name' => '',
                'status' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['name', 'status']);
        $this->assertDatabaseCount('materials', 0);
    }

    public function test_duplicate_generated_slugs_receive_incremental_suffixes(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('materials.store'), [
            'name' => 'Oro',
            'status' => 1,
        ]);
        $this->actingAs($admin)->post(route('materials.store'), [
            'name' => 'Oro',
            'status' => 1,
        ]);
        $this->actingAs($admin)->post(route('materials.store'), [
            'name' => 'Oro',
            'status' => 1,
        ]);

        $this->assertDatabaseHas('materials', ['slug' => 'oro']);
        $this->assertDatabaseHas('materials', ['slug' => 'oro-2']);
        $this->assertDatabaseHas('materials', ['slug' => 'oro-3']);
    }

    public function test_authenticated_user_can_view_material_edit_form(): void
    {
        $admin = User::factory()->create();
        $material = Material::create([
            'name' => 'Oro',
            'slug' => 'oro',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.edit', $material));

        $response
            ->assertOk()
            ->assertViewIs('materials.edit')
            ->assertViewHas('material', $material);
    }

    public function test_material_can_be_updated_and_slug_is_regenerated_when_name_changes(): void
    {
        $admin = User::factory()->create();
        $material = Material::create([
            'name' => 'Oro',
            'slug' => 'oro',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('materials.update', $material), [
                'name' => 'Oro blanco',
                'status' => 0,
            ]);

        $response
            ->assertRedirect(route('materials.index'))
            ->assertSessionHas('status', 'Material actualizado correctamente.');

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'name' => 'Oro blanco',
            'slug' => 'oro-blanco',
            'status' => 0,
        ]);
    }

    public function test_material_listing_searches_by_name(): void
    {
        $admin = User::factory()->create();
        Material::create(['name' => 'Oro', 'slug' => 'oro', 'status' => true]);
        Material::create(['name' => 'Plata', 'slug' => 'plata', 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.index', ['search' => 'Oro']));

        $response->assertViewHas('materials', function ($materials): bool {
            return $materials->total() === 1
                && $materials->first()->name === 'Oro';
        });
    }

    public function test_material_listing_searches_by_slug(): void
    {
        $admin = User::factory()->create();
        Material::create(['name' => 'Oro', 'slug' => 'oro', 'status' => true]);
        Material::create(['name' => 'Plata', 'slug' => 'plata', 'status' => true]);

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.index', ['search' => 'plata']));

        $response->assertViewHas('materials', function ($materials): bool {
            return $materials->total() === 1
                && $materials->first()->slug === 'plata';
        });
    }

    public function test_material_listing_filters_active_status(): void
    {
        $admin = User::factory()->create();
        Material::create(['name' => 'Oro', 'slug' => 'oro', 'status' => true]);
        Material::create(['name' => 'Plata', 'slug' => 'plata', 'status' => false]);

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.index', ['status' => '1']));

        $response->assertViewHas('materials', function ($materials): bool {
            return $materials->total() === 1
                && $materials->first()->status === true;
        });
    }

    public function test_material_listing_filters_inactive_status(): void
    {
        $admin = User::factory()->create();
        Material::create(['name' => 'Oro', 'slug' => 'oro', 'status' => true]);
        Material::create(['name' => 'Plata', 'slug' => 'plata', 'status' => false]);

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.index', ['status' => '0']));

        $response->assertViewHas('materials', function ($materials): bool {
            return $materials->total() === 1
                && $materials->first()->status === false;
        });
    }

    public function test_material_listing_paginates_ten_records_per_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Material::create([
                'name' => "Material {$index}",
                'slug' => "material-{$index}",
                'status' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.index'));

        $response->assertViewHas('materials', function ($materials): bool {
            return $materials instanceof LengthAwarePaginator
                && $materials->total() === 11
                && $materials->perPage() === 10
                && $materials->currentPage() === 1
                && $materials->count() === 10;
        });
    }

    public function test_material_listing_can_view_the_second_page(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Material::create([
                'name' => "Material {$index}",
                'slug' => "material-{$index}",
                'status' => true,
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.index', ['page' => 2]));

        $response->assertViewHas('materials', function ($materials): bool {
            return $materials instanceof LengthAwarePaginator
                && $materials->currentPage() === 2
                && $materials->count() === 1;
        });
    }

    public function test_material_filters_are_preserved_in_pagination_links(): void
    {
        $admin = User::factory()->create();

        for ($index = 1; $index <= 11; $index++) {
            Material::create([
                'name' => "Filtrado {$index}",
                'slug' => "filtrado-{$index}",
                'status' => true,
            ]);
        }

        Material::create([
            'name' => 'Otro material',
            'slug' => 'otro-material',
            'status' => false,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('materials.index', [
                'search' => 'Filtrado',
                'status' => '1',
            ]));

        $response
            ->assertOk()
            ->assertSee('search=Filtrado')
            ->assertSee('status=1')
            ->assertViewHas('materials', function ($materials): bool {
                return $materials instanceof LengthAwarePaginator
                    && $materials->total() === 11
                    && $materials->currentPage() === 1;
            });
    }

    public function test_material_is_soft_deleted(): void
    {
        $admin = User::factory()->create();
        $material = Material::create([
            'name' => 'Oro',
            'slug' => 'oro',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('materials.destroy', $material));

        $response
            ->assertRedirect(route('materials.index'))
            ->assertSessionHas('status', 'Material eliminado correctamente.');

        $this->assertSoftDeleted('materials', ['id' => $material->id]);
        $this->assertNotNull(Material::withTrashed()->find($material->id));
    }
}
