<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Nombre Viejo',
            'email' => 'viejo@tienda.com',
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Nombre Nuevo',
                'email' => 'nuevo@tienda.com',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre Nuevo',
            'email' => 'nuevo@tienda.com',
        ]);
    }
}
