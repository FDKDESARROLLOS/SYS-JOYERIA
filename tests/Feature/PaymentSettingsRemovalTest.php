<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PaymentSettingsRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_settings_screen_and_navigation_entry_are_removed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertFalse(Route::has('settings.payments'));

        $this->actingAs($admin)
            ->get('/settings/payments')
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('settings.store'))
            ->assertOk()
            ->assertDontSee('Pagos')
            ->assertDontSee('settings.payments', false);
    }

    public function test_technical_users_do_not_get_a_payment_settings_route(): void
    {
        $technical = User::factory()->create(['role' => 'technical']);

        $this->actingAs($technical)
            ->get('/settings/payments')
            ->assertNotFound();
    }
}
