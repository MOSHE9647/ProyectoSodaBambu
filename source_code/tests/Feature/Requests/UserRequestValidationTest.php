<?php

namespace Tests\Feature\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());
    }

    public function test_store_requires_name(): void
    {
        $this->actingAsAdmin();

        $response = $this->from(route('users.create'))->post(route('users.store'), [
            'name' => '',
            'email' => 'user.required.name@example.com',
            'role' => UserRole::ADMIN->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors('name');
    }

    public function test_store_rejects_name_longer_than_255_characters(): void
    {
        $this->actingAsAdmin();

        $response = $this->from(route('users.create'))->post(route('users.store'), [
            'name' => str_repeat('a', 256),
            'email' => 'user.max.name@example.com',
            'role' => UserRole::ADMIN->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_valid_and_unique_email(): void
    {
        $this->actingAsAdmin();

        User::factory()->withRole(UserRole::ADMIN)->create([
            'email' => 'duplicated.user@example.com',
        ]);

        $invalidFormat = $this->from(route('users.create'))->post(route('users.store'), [
            'name' => 'Usuario',
            'email' => 'invalid-email',
            'role' => UserRole::ADMIN->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $invalidFormat->assertRedirect(route('users.create'));
        $invalidFormat->assertSessionHasErrors('email');

        $duplicate = $this->from(route('users.create'))->post(route('users.store'), [
            'name' => 'Usuario',
            'email' => 'duplicated.user@example.com',
            'role' => UserRole::ADMIN->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $duplicate->assertRedirect(route('users.create'));
        $duplicate->assertSessionHasErrors('email');
    }

    public function test_store_requires_valid_role_enum(): void
    {
        $this->actingAsAdmin();

        $response = $this->from(route('users.create'))->post(route('users.store'), [
            'name' => 'Usuario',
            'email' => 'user.role@example.com',
            'role' => 'super-admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors('role');
    }

    public function test_store_rejects_short_password_or_missing_confirmation(): void
    {
        $this->actingAsAdmin();

        $short = $this->from(route('users.create'))->post(route('users.store'), [
            'name' => 'Usuario',
            'email' => 'user.short.pass@example.com',
            'role' => UserRole::ADMIN->value,
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $short->assertRedirect(route('users.create'));
        $short->assertSessionHasErrors('password');

        $withoutConfirmation = $this->from(route('users.create'))->post(route('users.store'), [
            'name' => 'Usuario',
            'email' => 'user.confirm.pass@example.com',
            'role' => UserRole::ADMIN->value,
            'password' => 'password123',
        ]);

        $withoutConfirmation->assertRedirect(route('users.create'));
        $withoutConfirmation->assertSessionHasErrors('password');
    }
}
