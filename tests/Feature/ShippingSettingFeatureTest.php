<?php

namespace Tests\Feature;

use App\Models\ShippingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingSettingFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_real_shipping_settings_without_demo_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $shippingSetting = ShippingSetting::create([
            'enabled' => true,
            'method_name' => 'Envío real',
            'estimated_time' => '3 a 6 días hábiles',
            'shipping_cost' => 80,
            'free_shipping_enabled' => true,
            'free_shipping_type' => 'minimum',
            'free_shipping_minimum' => 900,
        ]);
        config([
            'aurea-demo.shipping_settings.method_name' => 'Envío demo',
            'aurea-demo.shipping_settings.shipping_cost' => 999,
        ]);

        $response = $this->actingAs($admin)->get(route('settings.shipping'));

        $response
            ->assertOk()
            ->assertViewIs('settings.shipping')
            ->assertViewHas('shippingSetting', $shippingSetting)
            ->assertSee('value="Envío real"', false)
            ->assertSee('value="3 a 6 días hábiles"', false)
            ->assertSee('value="80.00"', false)
            ->assertSee('value="900.00"', false)
            ->assertSee('class="admin-form-label">Tiempo estimado</span>', false)
            ->assertDontSee('value="Envío demo"', false)
            ->assertDontSee('value="999.00"', false)
            ->assertSee('name="enabled"', false)
            ->assertDontSee('name="shipping_enabled"', false)
            ->assertDontSee('name="notes"', false)
            ->assertSee('action="'.route('settings.shipping.update').'"', false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="PATCH"', false);
    }

    public function test_admin_can_create_and_update_one_shipping_setting_with_normalization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('settings.shipping.update'), [
                'enabled' => true,
                'method_name' => 'Envío estándar',
                'shipping_cost' => '150.00',
                'free_shipping_enabled' => false,
                'free_shipping_type' => 'minimum',
                'free_shipping_minimum' => '1000.00',
            ])
            ->assertRedirect(route('settings.shipping'))
            ->assertSessionHas('status', 'La configuración de envío se actualizó correctamente.');

        $shippingSetting = ShippingSetting::query()->firstOrFail();

        $this->assertSame(1, ShippingSetting::query()->count());
        $this->assertNull($shippingSetting->estimated_time);
        $this->assertFalse($shippingSetting->free_shipping_enabled);
        $this->assertNull($shippingSetting->free_shipping_type);
        $this->assertNull($shippingSetting->free_shipping_minimum);

        $this->actingAs($admin)
            ->patch(route('settings.shipping.update'), [
                'enabled' => true,
                'method_name' => 'Envío actualizado',
                'estimated_time' => '1 a 3 días hábiles',
                'shipping_cost' => '125.50',
                'free_shipping_enabled' => true,
                'free_shipping_type' => 'all',
                'free_shipping_minimum' => '999.00',
            ])
            ->assertRedirect(route('settings.shipping'));

        $shippingSetting->refresh();

        $this->assertSame(1, ShippingSetting::query()->count());
        $this->assertSame($shippingSetting->id, ShippingSetting::query()->firstOrFail()->id);
        $this->assertSame('all', $shippingSetting->free_shipping_type);
        $this->assertSame('125.50', $shippingSetting->shipping_cost);
        $this->assertNull($shippingSetting->free_shipping_minimum);

        $this->actingAs($admin)
            ->patch(route('settings.shipping.update'), [
                'enabled' => true,
                'method_name' => 'Envío actualizado',
                'estimated_time' => '1 a 3 días hábiles',
                'shipping_cost' => '125.50',
                'free_shipping_enabled' => true,
                'free_shipping_type' => 'minimum',
                'free_shipping_minimum' => '750.00',
            ])
            ->assertRedirect(route('settings.shipping'));

        $this->assertSame(1, ShippingSetting::query()->count());
        $this->assertDatabaseHas('shipping_settings', [
            'id' => $shippingSetting->id,
            'method_name' => 'Envío actualizado',
            'shipping_cost' => '125.50',
            'free_shipping_type' => 'minimum',
            'free_shipping_minimum' => '750.00',
        ]);
    }

    public function test_shipping_setting_update_validates_required_and_non_negative_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('settings.shipping.update'), [
                'enabled' => true,
                'method_name' => '',
                'shipping_cost' => -1,
                'free_shipping_enabled' => false,
                'free_shipping_type' => 'invalid',
                'free_shipping_minimum' => 100,
            ])
            ->assertSessionHasErrors(['method_name', 'shipping_cost', 'free_shipping_type']);

        $this->actingAs($admin)
            ->patch(route('settings.shipping.update'), [
                'enabled' => true,
                'method_name' => 'Envío estándar',
                'shipping_cost' => 150,
                'free_shipping_enabled' => true,
                'free_shipping_type' => 'minimum',
                'free_shipping_minimum' => -1,
            ])
            ->assertSessionHasErrors('free_shipping_minimum')
            ->assertSessionHasInput('free_shipping_minimum', '-1');

        $this->assertSame(0, ShippingSetting::query()->count());
    }

    public function test_minimum_is_required_when_free_shipping_uses_minimum(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('settings.shipping.update'), [
                'enabled' => true,
                'method_name' => 'Envío estándar',
                'shipping_cost' => 150,
                'free_shipping_enabled' => true,
                'free_shipping_type' => 'minimum',
            ])
            ->assertSessionHasErrors('free_shipping_minimum');
    }

    public function test_technical_users_cannot_view_or_update_shipping_settings(): void
    {
        $technical = User::factory()->create(['role' => 'technical']);

        $this->actingAs($technical)
            ->get(route('settings.shipping'))
            ->assertForbidden();

        $this->actingAs($technical)
            ->patch(route('settings.shipping.update'), [])
            ->assertForbidden();

        $this->assertSame(0, ShippingSetting::query()->count());
    }
}
