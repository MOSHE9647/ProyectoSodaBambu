<?php

namespace Tests\Feature\Controllers;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_admin_role(): void
    {
        $employee = User::factory()->withRole(UserRole::EMPLOYEE)->create();
        $this->actingAs($employee);

        $response = $this->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_store_creates_new_admin_user(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $response = $this->post(route('users.store'), [
            'name' => 'Nuevo Admin',
            'email' => 'nuevo.admin@example.com',
            'role' => UserRole::ADMIN->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.index'));

        $created = User::where('email', 'nuevo.admin@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole(UserRole::ADMIN->value));
    }

    public function test_store_restores_soft_deleted_user_with_same_email(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());

        $deletedUser = User::factory()->withRole(UserRole::EMPLOYEE)->create([
            'name' => 'Usuario Borrado',
            'email' => 'restore.user@example.com',
        ]);
        $originalId = $deletedUser->id;
        $deletedUser->delete();

        $response = $this->post(route('users.store'), [
            'name' => 'Usuario Restaurado',
            'email' => 'restore.user@example.com',
            'role' => UserRole::ADMIN->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.index'));

        $restored = User::withTrashed()->where('email', 'restore.user@example.com')->first();

        $this->assertNotNull($restored);
        $this->assertSame($originalId, $restored->id);
        $this->assertNull($restored->deleted_at);
        $this->assertSame('Usuario Restaurado', $restored->name);
        $this->assertTrue($restored->hasRole(UserRole::ADMIN->value));
    }

    public function test_destroy_prevents_deleting_the_only_admin_user(): void
    {
        $admin = User::factory()->withRole(UserRole::ADMIN)->create();
        $this->actingAs($admin);

        $response = $this->delete(route('users.destroy', $admin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'deleted_at' => null,
        ]);
    }

    public function test_destroy_soft_deletes_related_employee_when_present(): void
    {
        $admin = User::factory()->withRole(UserRole::ADMIN)->create();
        $this->actingAs($admin);

        $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create();
        $employee = Employee::factory()->create(['id' => $employeeUser->id]);

        $response = $this->delete(route('users.destroy', $employeeUser));

        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $employeeUser->id]);
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_update_modifies_existing_user_data(): void
    {
        $admin = User::factory()->withRole(UserRole::ADMIN)->create();
        $this->actingAs($admin);

        $target = User::factory()->withRole(UserRole::ADMIN)->create([
            'name' => 'Nombre Inicial',
            'email' => 'inicial.user@example.com',
        ]);

        $response = $this->put(route('users.update', $target), [
            'name' => 'Nombre Actualizado',
            'email' => 'inicial.user@example.com',
            'role' => UserRole::ADMIN->value,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Nombre Actualizado',
            'email' => 'inicial.user@example.com',
        ]);
    }

    public function test_update_rejects_duplicate_active_user_email(): void
    {
        $admin = User::factory()->withRole(UserRole::ADMIN)->create();
        $this->actingAs($admin);

        User::factory()->withRole(UserRole::ADMIN)->create([
            'email' => 'duplicado.user@example.com',
        ]);

        $target = User::factory()->withRole(UserRole::ADMIN)->create([
            'email' => 'objetivo.user@example.com',
        ]);

        $response = $this->from(route('users.edit', $target))
            ->put(route('users.update', $target), [
                'name' => 'Objetivo',
                'email' => 'duplicado.user@example.com',
                'role' => UserRole::ADMIN->value,
            ]);

        $response->assertRedirect(route('users.edit', $target));
        $response->assertSessionHasErrors('email');
    }
}
