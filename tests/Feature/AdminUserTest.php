<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_admin_users_list(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('settings.admin-users.index'));

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_view_admin_user_creation_form(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('settings.admin-users.create'));

        $response
            ->assertOk()
            ->assertViewIs('settings.admin-users.create');
    }

    public function test_authenticated_user_can_view_admin_user_edit_form(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('settings.admin-users.edit', $user));

        $response
            ->assertOk()
            ->assertViewIs('settings.admin-users.edit')
            ->assertViewHas('user', $user);
    }

    public function test_admin_user_can_be_created(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.admin-users.store'), [
                'name' => 'Usuario Prueba',
                'email' => 'usuario@tienda.com',
                'role' => 'admin',
                'status' => 1,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response
            ->assertRedirect(route('settings.admin-users.index'))
            ->assertSessionHas('status', 'Usuario creado correctamente.');

        $this->assertDatabaseHas('users', [
            'name' => 'Usuario Prueba',
            'email' => 'usuario@tienda.com',
            'role' => 'admin',
            'status' => 1,
        ]);

        $createdUser = User::where('email', 'usuario@tienda.com')->firstOrFail();

        $this->assertTrue(Hash::check('Password123!', $createdUser->password));
    }

    public function test_admin_user_creation_requires_valid_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.admin-users.store'), []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'role',
            'status',
            'password',
        ]);
    }

    public function test_admin_user_creation_rejects_invalid_role_status_and_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.admin-users.store'), [
                'name' => 'Usuario Inválido',
                'email' => 'usuario-invalido@tienda.com',
                'role' => 'customer',
                'status' => 'unknown',
                'password' => 'short',
                'password_confirmation' => 'different',
            ]);

        $response->assertSessionHasErrors([
            'role',
            'status',
            'password',
        ]);
    }

    public function test_admin_user_creation_rejects_duplicate_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $existingUser = User::factory()->create([
            'email' => 'repetido@tienda.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.admin-users.store'), [
                'name' => 'Usuario Repetido',
                'email' => $existingUser->email,
                'role' => 'admin',
                'status' => 1,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_user_can_be_updated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Nombre Viejo',
            'email' => 'viejo@tienda.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('settings.admin-users.update', $user), [
                'name' => 'Nombre Nuevo',
                'email' => 'nuevo@tienda.com',
                'role' => 'technical',
                'status' => 1,
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Usuario actualizado correctamente.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre Nuevo',
            'email' => 'nuevo@tienda.com',
            'role' => 'technical',
        ]);
    }

    public function test_admin_user_can_keep_their_existing_email_when_updated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'usuario@tienda.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('settings.admin-users.update', $user), [
                'name' => 'Nombre Actualizado',
                'email' => $user->email,
                'role' => 'admin',
                'status' => 1,
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre Actualizado',
            'email' => 'usuario@tienda.com',
        ]);
    }

    public function test_admin_user_update_requires_valid_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->patch(route('settings.admin-users.update', $user), []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'role',
            'status',
        ]);
    }

    public function test_admin_user_update_rejects_duplicate_email(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'usuario@tienda.com',
        ]);

        User::factory()->create([
            'email' => 'otro@tienda.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('settings.admin-users.update', $user), [
                'name' => 'Usuario Actualizado',
                'email' => 'otro@tienda.com',
                'role' => 'technical',
                'status' => 1,
            ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'usuario@tienda.com',
        ]);
    }

    public function test_admin_user_can_be_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->delete(route('settings.admin-users.destroy', $user));

        $response
            ->assertRedirect(route('settings.admin-users.index'))
            ->assertSessionHas('status', 'Usuario eliminado correctamente.');

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'status' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('settings.admin-users.destroy', $user));

        $response->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }
}
