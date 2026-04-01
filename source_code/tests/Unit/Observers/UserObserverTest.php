<?php

use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

/**
 * Unit Test: User Observer - Cascading Deletion
 * Tests the UserObserver to ensure employee records are properly deleted
 * when their associated user account is deleted.
 */
test('CP-06_EIF-20_QA2 - deletes associated employee when user is deleted', function () {
    // Given: a user with an associated employee record
    $user = User::factory()->withRole(UserRole::EMPLOYEE)->create();
    Employee::factory()->create([
        'id' => $user->id,
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ]);

    // Verify employee exists
    expect(Employee::where('id', $user->id)->exists())->toBeTrue();

    // When: deleting the user
    $user->delete();

    // Then: the associated employee is also deleted
    expect(Employee::where('id', $user->id)->exists())->toBeFalse();
});

test('CP-06_EIF-20_QA2 - soft-deletes associated employee when user is deleted', function () {
    // Given: a user with an employee that is already soft-deleted
    $user = User::factory()->withRole(UserRole::EMPLOYEE)->create();
    $employee = Employee::factory()->create([
        'id' => $user->id,
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ]);
    $employee->delete(); // soft delete

    // Verify soft-deleted employee exists with trashed
    expect(Employee::withTrashed()->where('id', $user->id)->exists())->toBeTrue();
    expect(Employee::where('id', $user->id)->exists())->toBeFalse();

    // When: deleting the user
    $user->delete();

    // Then: the soft-deleted employee remains soft-deleted (observer only soft-deletes)
    expect(Employee::withTrashed()->where('id', $user->id)->exists())->toBeTrue();
});

test('CP-06_EIF-20_QA2 - user without employee record can be deleted safely', function () {
    // Given: an admin user without employee record
    $admin = User::factory()->withRole(UserRole::ADMIN)->create();

    // Verify no employee exists
    expect(Employee::where('id', $admin->id)->exists())->toBeFalse();

    // When: deleting the admin
    $admin->delete();

    // Then: deletion succeeds without errors
    expect(User::where('id', $admin->id)->exists())->toBeFalse();
});

test('CP-06_EIF-20_QA2 - observer triggers only on user deletion', function () {
    // Given: a user with an employee
    $user = User::factory()->withRole(UserRole::EMPLOYEE)->create();
    Employee::factory()->create([
        'id' => $user->id,
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ]);

    // When: updating the user (not deleting)
    $user->update(['email' => 'newemail@test.com']);

    // Then: employee is NOT deleted
    expect(Employee::where('id', $user->id)->exists())->toBeTrue();
});

test('CP-06_EIF-20_QA2 - deletes correct employee when multiple users exist', function () {
    // Given: multiple users, each with their own employee
    $user1 = User::factory()->withRole(UserRole::EMPLOYEE)->create();
    $user2 = User::factory()->withRole(UserRole::EMPLOYEE)->create();
    Employee::factory()->create([
        'id' => $user1->id,
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 5000,
    ]);
    Employee::factory()->create([
        'id' => $user2->id,
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 6000,
    ]);

    // When: deleting user1
    $user1->delete();

    // Then: only user1's employee is deleted, user2's remains
    expect(Employee::where('id', $user1->id)->exists())->toBeFalse();
    expect(Employee::where('id', $user2->id)->exists())->toBeTrue();
});
