<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_admin_can_login(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => true,
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_technical_user_is_redirected_to_the_logs_screen(): void
    {
        $user = User::factory()->create([
            'role' => 'technical',
            'status' => true,
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('settings.errors.logs'));
        $this->get(route('settings.errors.logs'))->assertOk();
    }

    public function test_unknown_role_is_not_given_technical_navigation(): void
    {
        $user = User::factory()->create([
            'role' => 'unknown',
            'status' => true,
        ]);

        $response = $this->actingAs($user)->get(route('profile'));

        $response
            ->assertOk()
            ->assertSee('Mi perfil')
            ->assertDontSee('Catálogo de errores')
            ->assertDontSee('Registro de errores');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => false,
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/logout');

        $this->assertGuest();

        $response->assertRedirect(route('login'));
    }
}
