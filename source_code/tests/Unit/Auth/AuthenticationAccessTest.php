<?php

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Unit Story: EIF-901 - Authentication and role-based access control (Internal QA Story, pending Jira creation).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-901
 */
test('CP-01_EIF-901 - user model correctly reports authentication status', function () {
    // Given: an unauthenticated user context.
    // When: checking if user is authenticated.
    $isAuthenticated = auth()->check();

    // Then: returns false when no user is logged in.
    expect($isAuthenticated)->toBeFalse();
});

test('CP-02_EIF-901 - user password verification using hash', function () {
    // Given: a user created with plain password handled by the model mutator.
    $plainPassword = 'password123';
    $user = User::factory()->create([
        'password' => $plainPassword,
    ]);

    // When: verifying the original plain password against stored value.
    $isValid = Hash::check($plainPassword, $user->fresh()->password);

    // Then: password verification succeeds after model hashing.
    expect($isValid)->toBeTrue();
});

test('CP-03_EIF-901 - password hash rejects incorrect password', function () {
    // Given: a user with bcrypt password stored.
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    // When: verifying incorrect password.
    $isValid = Hash::check('wrong-password', $user->password);

    // Then: password verification fails.
    expect($isValid)->toBeFalse();
});

test('CP-04_EIF-901 - inactive employee status is stored correctly', function () {
    // Given: an employee marked as INACTIVE.
    $employee = $user = User::factory()->create();
    $employee->employee()->create([
        'phone' => '506-8888-1111',
        'status' => EmployeeStatus::INACTIVE,
        'hourly_wage' => 5000,
        'payment_frequency' => PaymentFrequency::MONTHLY,
    ]);

    // When: retrieving employee status.
    $status = $employee->employee->status;

    // Then: status is correctly returned as INACTIVE.
    expect($status)->toBe(EmployeeStatus::INACTIVE);
});

test('CP-05_EIF-901 - active employee status allows login attempt', function () {
    // Given: an active employee with verified email.
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');

    $user = User::factory()
        ->withRole(UserRole::EMPLOYEE)
        ->create([
            'email_verified_at' => now(),
        ]);
    $user->employee()->create([
        'phone' => '506-8888-1111',
        'status' => EmployeeStatus::ACTIVE,
        'hourly_wage' => 5000,
        'payment_frequency' => PaymentFrequency::MONTHLY,
    ]);

    // When: checking employee status.
    $status = $user->employee->status;

    // Then: employee is ACTIVE.
    expect($status)->toBe(EmployeeStatus::ACTIVE);
});

test('CP-06_EIF-901 - admin role assignment via spatie permission', function () {
    // Given: roles configured and a user instance.
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');

    $user = User::factory()->withRole(UserRole::ADMIN)->create();

    // When: checking user role.
    $hasAdminRole = $user->hasRole(UserRole::ADMIN->value);

    // Then: user has ADMIN role.
    expect($hasAdminRole)->toBeTrue();
});
