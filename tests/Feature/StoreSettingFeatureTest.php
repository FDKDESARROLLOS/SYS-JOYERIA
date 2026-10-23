<?php

namespace Tests\Feature;

use App\Models\ShippingSetting;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSettingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_real_store_setting_without_demo_only_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $storeSetting = StoreSetting::create([
            'store_name' => 'Joyería Real',
            'email' => 'contacto@joyeria-real.test',
            'address' => 'Avenida Reforma 123',
            'phone' => '+52 55 1111 1111',
            'whatsapp' => '+52 55 2222 2222',
        ]);
        config(['aurea-demo.store_settings.store_name' => 'Tienda Demo']);

        $response = $this->actingAs($admin)->get(route('settings.store'));

        $response
            ->assertOk()
            ->assertViewIs('settings.store')
            ->assertViewHas('storeSetting', $storeSetting)
            ->assertSee('value="Joyería Real"', false)
            ->assertSee('value="contacto@joyeria-real.test"', false)
            ->assertSee('name="whatsapp"', false)
            ->assertSee('value="+52 55 2222 2222"', false)
            ->assertSee('value="Avenida Reforma 123"', false)
            ->assertSee('action="'.route('settings.store.update').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="PATCH"', false)
            ->assertDontSee('value="Tienda Demo"', false)
            ->assertDontSee('name="phone"', false)
            ->assertDontSee('name="shipping_cost"', false)
            ->assertDontSee('name="legal_name"', false)
            ->assertDontSee('name="currency"', false)
            ->assertDontSee('name="timezone"', false)
            ->assertDontSee('name="tax_percentage"', false);
    }

    public function test_admin_can_create_and_update_the_single_store_setting_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shippingSetting = ShippingSetting::create([
            'method_name' => 'Envío estándar',
            'shipping_cost' => 150,
        ]);

        $this->actingAs($admin)
            ->patch(route('settings.store.update'), [
                'store_name' => 'Joyería Inicial',
                'email' => 'inicial@joyeria.test',
                'address' => 'Dirección inicial',
                'phone' => '+52 55 3333 3333',
                'whatsapp' => '+52 55 5555 5555',
                'shipping_cost' => 999,
            ])
            ->assertRedirect(route('settings.store'))
            ->assertSessionHas('status', 'Los datos de la tienda se actualizaron correctamente.');

        $storeSetting = StoreSetting::query()->firstOrFail();

        $this->assertSame(1, StoreSetting::query()->count());
        $this->assertSame('Joyería Inicial', $storeSetting->store_name);
        $this->assertSame('inicial@joyeria.test', $storeSetting->email);
        $this->assertSame('Dirección inicial', $storeSetting->address);
        $this->assertSame('+52 55 5555 5555', $storeSetting->whatsapp);
        $this->assertNull($storeSetting->phone);
        $this->assertSame('150.00', $shippingSetting->refresh()->shipping_cost);

        $storeSetting->update([
            'phone' => '+52 55 3333 3333',
            'whatsapp' => '+52 55 4444 4444',
        ]);

        $this->actingAs($admin)
            ->patch(route('settings.store.update'), [
                'store_name' => 'Joyería Actualizada',
                'email' => 'actualizada@joyeria.test',
                'address' => 'Dirección actualizada',
            ])
            ->assertRedirect(route('settings.store'));

        $this->assertSame(1, StoreSetting::query()->count());
        $this->assertSame($storeSetting->id, StoreSetting::query()->firstOrFail()->id);
        $this->assertDatabaseHas('store_settings', [
            'id' => $storeSetting->id,
            'store_name' => 'Joyería Actualizada',
            'email' => 'actualizada@joyeria.test',
            'address' => 'Dirección actualizada',
            'phone' => '+52 55 3333 3333',
            'whatsapp' => '+52 55 4444 4444',
        ]);
    }

    public function test_store_setting_update_validates_name_and_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('settings.store.update'), [
                'store_name' => '',
                'email' => 'correo-invalido',
                'whatsapp' => '+52 33 1234 5678',
                'address' => 'Dirección',
            ])
            ->assertSessionHasErrors(['store_name', 'email'])
            ->assertSessionHasInput('whatsapp', '+52 33 1234 5678');

        $this->assertSame(0, StoreSetting::query()->count());
    }

    public function test_technical_users_cannot_view_or_update_store_settings(): void
    {
        $technical = User::factory()->create(['role' => 'technical']);

        $this->actingAs($technical)
            ->get(route('settings.store'))
            ->assertForbidden();

        $this->actingAs($technical)
            ->patch(route('settings.store.update'), [
                'store_name' => 'No autorizado',
            ])
            ->assertForbidden();

        $this->assertSame(0, StoreSetting::query()->count());
    }
}
