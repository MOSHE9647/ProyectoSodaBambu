<?php

use App\Actions\Users\UpsertUserAction;
use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-01_EIF-20_QA2 - executes upsert action with correct parameters', function () {
    // Given: role configuration and user data with employee details.
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');
    Role::findOrCreate(UserRole::ADMIN->value, 'web');

    $action = new UpsertUserAction;

    // When: calling execute with userData, roleName, and employeeData parameters.
    $user = $action->execute(
        ['name' => 'Test User', 'email' => 'test@example.com', 'password' => 'password123'],
        UserRole::EMPLOYEE->value,
        [
            'phone' => '506-8888-1111',
            'status' => EmployeeStatus::ACTIVE->value,
            'hourly_wage' => 5000,
            'payment_frequency' => PaymentFrequency::MONTHLY->value,
        ]
    );

    // Then: user is created with stored attributes.
    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBe('test@example.com')
        ->and($user->hasRole(UserRole::EMPLOYEE->value))->toBeTrue();
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-02_EIF-20_QA2 - creates admin user without requiring employee data', function () {
    // Given: roles configured and admin data.
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');

    $action = new UpsertUserAction;

    // When: creating admin with empty employeeData array.
    $user = $action->execute(
        ['name' => 'Admin', 'email' => 'admin2@example.com', 'password' => 'password123'],
        UserRole::ADMIN->value,
        []
    );

    // Then: admin user is created with ADMIN role assigned.
    expect($user->hasRole(UserRole::ADMIN->value))->toBeTrue();
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-03_EIF-20_QA2 - validates hourly wage is numeric and positive', function () {
    // Given: employee data with invalid hourly wage.
    $payload = [
        'phone' => '506-8888-1111',
        'status' => EmployeeStatus::ACTIVE->value,
        'hourly_wage' => -1000,
        'payment_frequency' => PaymentFrequency::MONTHLY->value,
    ];

    // When / Then: validation rejects negative wages.
    expect(fn () => Validator::make($payload, EmployeeRequest::rulesFor())->validate())
        ->toThrow(ValidationException::class);
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-04_EIF-20_QA2 - handles role transition from employee to admin', function () {
    // Given: an employee user with employee record.
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');

    $user = User::factory()
        ->withRole(UserRole::EMPLOYEE)
        ->create(['email' => 'transition@example.com']);
    $user->employee()->create([
        'phone' => '506-8888-1111',
        'status' => EmployeeStatus::ACTIVE,
        'hourly_wage' => 5000,
        'payment_frequency' => PaymentFrequency::MONTHLY,
    ]);

    $action = new UpsertUserAction;

    // When: changing role to ADMIN without employee data.
    $updated = $action->execute(
        ['name' => $user->name, 'email' => $user->email],
        UserRole::ADMIN->value,
        [],
        $user
    );

    // Then: role is synced to ADMIN.
    expect($updated->hasRole(UserRole::ADMIN->value))->toBeTrue();
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-05_EIF-20_QA2 - user model has cascading delete observer', function () {
    // Given: a user with employee relationship.
    $user = User::factory()->create();
    $employee = $user->employee()->create([
        'phone' => '506-8888-1111',
        'status' => EmployeeStatus::ACTIVE,
        'hourly_wage' => 5000,
        'payment_frequency' => PaymentFrequency::MONTHLY,
    ]);

    // When: user is soft-deleted via observer.
    $user->delete();

    // Then: employee is also soft-deleted (cascading).
    expect($employee->fresh()->trashed())->toBeTrue();
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-06_EIF-20_QA2 - employee record contains required contact information', function () {
    // Given: an employee with complete profile.
    $employee = Employee::factory()->create([
        'phone' => '506-8888-1111',
        'status' => EmployeeStatus::ACTIVE,
    ]);

    // When: accessing employee attributes.
    // Then: all required fields are present and correct type.
    expect($employee->phone)->toBeString()
        ->and($employee->status)->toBe(EmployeeStatus::ACTIVE)
        ->and($employee->hourly_wage_raw)->toBeFloat();
});

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-07_EIF-20_QA2 - user role assignment via spatie permission', function () {
    // Given: roles configured in database.
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');

    $user = User::factory()->create();

    // When: syncing roles using Spatie Permission.
    $user->syncRoles([UserRole::ADMIN->value]);

    // Then: user has the assigned role.
    expect($user->hasRole(UserRole::ADMIN->value))->toBeTrue()
        ->and($user->hasRole(UserRole::EMPLOYEE->value))->toBeFalse();
});
