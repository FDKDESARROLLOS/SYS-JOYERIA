<?php

namespace Tests\Feature;

use App\Models\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_persistent_error_catalog(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $errorCode = ErrorCode::create([
            'code' => 'REAL-001',
            'module' => 'Sistema',
            'title' => 'Error persistente',
            'description' => 'Descripción persistente.',
            'severity' => 'error',
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('settings.errors'));

        $response
            ->assertOk()
            ->assertViewIs('settings.errors')
            ->assertSee($errorCode->code)
            ->assertSee($errorCode->description)
            ->assertDontSee('AUTH-001')
            ->assertViewHas('errorCodes', fn ($codes): bool => $codes->contains('id', $errorCode->id));
    }

    public function test_admin_and_technical_users_can_access_the_catalog_and_logs(): void
    {
        foreach (['admin', 'technical'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('settings.errors'))->assertOk();
            $this->actingAs($user)->get(route('settings.errors.logs'))->assertOk();
        }
    }

    public function test_other_authenticated_roles_cannot_access_the_error_module(): void
    {
        $user = User::factory()->create(['role' => 'support']);

        $this->actingAs($user)->get(route('settings.errors'))->assertForbidden();
        $this->actingAs($user)->get(route('settings.errors.logs'))->assertForbidden();
        $this->actingAs($user)->get(route('settings.errors.create'))->assertForbidden();
    }

    public function test_admin_can_create_an_error_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('settings.errors.store'), [
            'code' => 'INV-100',
            'module' => 'Inventario',
            'title' => 'Stock no disponible',
            'description' => 'La existencia no es suficiente.',
            'severity' => 'warning',
            'status' => '1',
        ]);

        $response
            ->assertRedirect(route('settings.errors'))
            ->assertSessionHas('status', 'Código de error creado correctamente.');

        $this->assertDatabaseHas('error_codes', [
            'code' => 'INV-100',
            'severity' => 'warning',
            'status' => true,
        ]);
    }

    public function test_error_code_update_allows_the_same_code_and_updates_persisted_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $errorCode = ErrorCode::create([
            'code' => 'PAY-100',
            'module' => 'Pagos',
            'title' => 'Pago pendiente',
            'severity' => 'info',
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('settings.errors.update', $errorCode), [
            'code' => 'PAY-100',
            'module' => 'Pagos',
            'title' => 'Pago requiere revisión',
            'description' => 'Descripción actualizada.',
            'severity' => 'error',
            'status' => '0',
        ]);

        $response->assertRedirect(route('settings.errors'));
        $this->assertDatabaseHas('error_codes', [
            'id' => $errorCode->id,
            'code' => 'PAY-100',
            'title' => 'Pago requiere revisión',
            'severity' => 'error',
            'status' => false,
        ]);
    }

    public function test_error_code_rejects_duplicate_codes_and_invalid_severity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ErrorCode::create([
            'code' => 'DUP-001',
            'module' => 'Sistema',
            'title' => 'Código existente',
            'severity' => 'error',
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('settings.errors.store'), [
            'code' => 'DUP-001',
            'module' => 'Sistema',
            'title' => 'Código duplicado',
            'severity' => 'critical',
            'status' => '1',
        ]);

        $response->assertSessionHasErrors(['code', 'severity']);
    }

    public function test_error_code_status_can_be_toggled_without_deleting_the_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $errorCode = ErrorCode::create([
            'code' => 'STATUS-001',
            'module' => 'Sistema',
            'title' => 'Estado configurable',
            'severity' => 'info',
            'status' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('settings.errors.status', $errorCode))
            ->assertRedirect(route('settings.errors'));

        $this->assertDatabaseHas('error_codes', [
            'id' => $errorCode->id,
            'status' => false,
        ]);
    }

    public function test_catalog_filters_search_module_severity_status_and_paginates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ErrorCode::create([
            'code' => 'FILTER-001',
            'module' => 'Inventario',
            'title' => 'Buscar por descripción',
            'description' => 'Texto único de búsqueda.',
            'severity' => 'warning',
            'status' => false,
        ]);

        foreach (range(1, 10) as $number) {
            ErrorCode::create([
                'code' => "PAGE-{$number}",
                'module' => 'Sistema',
                'title' => "Código {$number}",
                'severity' => 'info',
                'status' => true,
            ]);
        }

        $filteredResponse = $this->actingAs($admin)->get(route('settings.errors', [
            'search' => 'Texto único',
            'module' => 'Inventario',
            'severity' => 'warning',
            'status' => '0',
        ]));

        $filteredResponse->assertViewHas('errorCodes', function ($codes): bool {
            return $codes->total() === 1 && $codes->first()->code === 'FILTER-001';
        });

        $paginatedResponse = $this->actingAs($admin)->get(route('settings.errors'));

        $paginatedResponse->assertViewHas('errorCodes', function ($codes): bool {
            return $codes->total() === 11 && $codes->perPage() === 10 && $codes->count() === 10;
        });
    }
}
