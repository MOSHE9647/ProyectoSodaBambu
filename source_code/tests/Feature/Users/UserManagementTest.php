<?php

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');
    Role::findOrCreate(UserRole::GUEST->value, 'web');
});

function createAdminActor(string $password = 'password123'): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create([
        'password' => $password,
        'email_verified_at' => now(),
    ]);
}

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-01_EIF-20_QA2 - allows admin to create an employee user with employee profile', function () {
    // Given: an authenticated admin with access to user management.
    $admin = createAdminActor();

    // When: the admin submits a valid employee user creation payload.
    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Employee One',
        'email' => 'employee.one@example.com',
        'role' => UserRole::EMPLOYEE->value,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '506-8888-1111',
        'status' => EmployeeStatus::ACTIVE->value,
        'hourly_wage' => 2000,
        'payment_frequency' => PaymentFrequency::BIWEEKLY->value,
    ]);

    // Then: a user and related employee profile are persisted and success flash is shown.
    $response
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', 'Usuario creado correctamente.');

    $user = User::query()->where('email', 'employee.one@example.com')->firstOrFail();

    expect($user->hasRole(UserRole::EMPLOYEE->value))->toBeTrue();

    $this->assertDatabaseHas('employees', [
        'id' => $user->id,
        'phone' => '506-8888-1111',
        'status' => EmployeeStatus::ACTIVE->value,
        'payment_frequency' => PaymentFrequency::BIWEEKLY->value,
    ]);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-02_EIF-20_QA2 - creates an admin user without creating employee profile', function () {
    // Given: an authenticated admin actor.
    $admin = createAdminActor();

    // When: a new user is created with ADMIN role.
    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Admin Two',
        'email' => 'admin.two@example.com',
        'role' => UserRole::ADMIN->value,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Then: the user is created as admin and no employee row is generated.
    $response->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'admin.two@example.com')->firstOrFail();

    expect($user->hasRole(UserRole::ADMIN->value))->toBeTrue();

    $this->assertDatabaseMissing('employees', [
        'id' => $user->id,
    ]);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-03_EIF-20_QA2 - validates required employee fields when role is employee', function () {
    // Given: an authenticated admin actor.
    $admin = createAdminActor();

    // When: role is employee but employee profile fields are missing.
    $response = $this->actingAs($admin)->from(route('users.create'))->post(route('users.store'), [
        'name' => 'Employee Invalid',
        'email' => 'employee.invalid@example.com',
        'role' => UserRole::EMPLOYEE->value,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    // Then: request is rejected with validation errors and no user gets created.
    $response
        ->assertRedirect(route('users.create'))
        ->assertSessionHasErrors(['phone', 'status', 'hourly_wage', 'payment_frequency']);

    $this->assertDatabaseMissing('users', [
        'email' => 'employee.invalid@example.com',
    ]);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-04_EIF-20_QA2 - updates user role from employee to admin and deletes employee profile', function () {
    // Given: an admin and an existing employee user with employee profile.
    $admin = createAdminActor();

    $user = User::factory()->withRole(UserRole::EMPLOYEE)->create([
        'email_verified_at' => now(),
    ]);

    Employee::factory()->create([
        'id' => $user->id,
        'phone' => '8888-2222',
        'status' => EmployeeStatus::ACTIVE,
        'hourly_wage' => 1900,
        'payment_frequency' => PaymentFrequency::MONTHLY,
    ]);

    // When: the admin updates the same user to ADMIN role.
    $response = $this->actingAs($admin)->put(route('users.update', $user), [
        'name' => 'Updated Role User',
        'email' => $user->email,
        'role' => UserRole::ADMIN->value,
        'password' => null,
        'password_confirmation' => null,
    ]);

    // Then: role is switched and related employee profile is soft-deleted.
    $response
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('success', 'Usuario actualizado correctamente.');

    expect($user->fresh()->hasRole(UserRole::ADMIN->value))->toBeTrue();

    $this->assertSoftDeleted('employees', [
        'id' => $user->id,
    ]);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-05_EIF-20_QA2 - blocks admin from deleting own account', function () {
    // Given: an authenticated admin trying to delete their own user.
    $admin = createAdminActor();

    // When: delete endpoint is called for the same authenticated user.
    $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

    // Then: operation is denied with policy message and user remains active.
    $response
        ->assertRedirect()
        ->assertSessionHas('error', 'No puedes eliminar tu propia cuenta.');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'deleted_at' => null,
    ]);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-06_EIF-20_QA2 - admin can delete a non-admin user and soft-delete employee profile', function () {
    // Given: an authenticated admin and a target employee user with employee profile.
    $admin = createAdminActor();
    $targetUser = User::factory()->withRole(UserRole::EMPLOYEE)->create(['email_verified_at' => now()]);

    Employee::factory()->create([
        'id' => $targetUser->id,
        'phone' => '8888-3333',
        'status' => EmployeeStatus::ACTIVE,
        'hourly_wage' => 1950,
        'payment_frequency' => PaymentFrequency::MONTHLY,
    ]);

    // When: the admin deletes the non-admin user account.
    $response = $this->actingAs($admin)->delete(route('users.destroy', $targetUser));

    // Then: user is soft-deleted and related employee profile is also soft-deleted.
    $response
        ->assertRedirect()
        ->assertSessionHas('success', 'Usuario eliminado correctamente.');

    $this->assertSoftDeleted('users', [
        'id' => $targetUser->id,
    ]);

    $this->assertSoftDeleted('employees', [
        'id' => $targetUser->id,
    ]);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-07_EIF-20_QA2 - non-admin users cannot access user management routes', function () {
    // Given: an authenticated employee user without admin role.
    $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create(['email_verified_at' => now()]);

    // When: the user requests user management index and create routes.
    $this->actingAs($employeeUser)
        ->get(route('users.index'))
        ->assertForbidden();

    // Then: both routes are protected and return forbidden.
    $this->actingAs($employeeUser)
        ->get(route('users.create'))
        ->assertForbidden();
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-08_EIF-20_QA2 - displays user detail page', function () {
    // Given: an authenticated admin and an existing user.
    $admin = createAdminActor();
    $user = User::factory()->withRole(UserRole::EMPLOYEE)->create(['email_verified_at' => now()]);

    // When: the admin views the user detail page.
    $response = $this->actingAs($admin)->get(route('users.show', $user));

    // Then: the page displays user information.
    $response
        ->assertSuccessful()
        ->assertViewHas('user');
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-09_EIF-20_QA2 - displays user edit form with current values', function () {
    // Given: an authenticated admin and an existing user.
    $admin = createAdminActor();
    $user = User::factory()->withRole(UserRole::ADMIN)->create(['email_verified_at' => now()]);

    // When: the admin requests the user edit form.
    $response = $this->actingAs($admin)->get(route('users.edit', $user));

    // Then: the form displays current user data.
    $response
        ->assertSuccessful()
        ->assertViewHas('user', $user);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-10_EIF-20_QA2 - validates email uniqueness when updating user', function () {
    // Given: an authenticated admin and two existing users.
    $admin = createAdminActor();
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    // When: attempting to update user2 with user1's email.
    $response = $this->actingAs($admin)
        ->from(route('users.edit', $user2))
        ->put(route('users.update', $user2), [
            'name' => 'Updated User',
            'email' => 'user1@example.com',
            'role' => UserRole::EMPLOYEE->value,
        ]);

    // Then: validation fails due to email uniqueness constraint.
    $response
        ->assertRedirect(route('users.edit', $user2))
        ->assertSessionHasErrors(['email']);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-11_EIF-20_QA2 - lists all users in JSON format for DataTables', function () {
    // Given: an authenticated admin and multiple users in database.
    $admin = createAdminActor();
    User::factory()->count(3)->create(['email_verified_at' => now()]);

    // When: the admin requests users data via AJAX for DataTables.
    $response = $this->actingAs($admin)->get(route('users.index'), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    // Then: all users are returned in JSON format.
    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'roles'],
            ],
        ]);
});
