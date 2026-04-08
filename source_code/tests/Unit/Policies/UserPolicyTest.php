<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\UserPolicy;

/**
 * Epic: EIF-20_QA2 - User and employee lifecycle management for administrators (Internal QA Story).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 *
 * Tests the UserPolicy delete authorization logic to ensure:
 * 1. Users cannot delete their own account
 * 2. The last admin account cannot be deleted
 * 3. Other users can be deleted normally
 */
test('CP-05_EIF-20_QA2 - denies deletion of own account', function () {
    // Given: an admin user attempting to delete their own account
    $admin = User::factory()->withRole(UserRole::ADMIN)->create();

    // When: the delete policy is evaluated with same user
    $policy = new UserPolicy;
    $response = $policy->delete($admin, $admin);

    // Then: deletion is denied with appropriate message
    expect($response->allowed())->toBeFalse();
    expect($response->message())->toContain('propia cuenta');
});

test('CP-06_EIF-20_QA2 - allows deletion of non-admin user by admin', function () {
    // Given: an admin user attempting to delete another admin
    $authenticatedAdmin = User::factory()->withRole(UserRole::ADMIN)->create();
    $otherEmployee = User::factory()->withRole(UserRole::EMPLOYEE)->create();

    // When: the delete policy is evaluated
    $policy = new UserPolicy;
    $response = $policy->delete($authenticatedAdmin, $otherEmployee);

    // Then: deletion is allowed
    expect($response->allowed())->toBeTrue();
});

test('CP-06_EIF-20_QA2 - allows deletion of admin when multiple admins exist', function () {
    // Given: multiple admin users in system
    $authenticatedAdmin = User::factory()->withRole(UserRole::ADMIN)->create();
    $adminToDelete = User::factory()->withRole(UserRole::ADMIN)->create();
    User::factory()->withRole(UserRole::ADMIN)->create(); // Third admin

    // When: deleting one admin while others remain
    $policy = new UserPolicy;
    $response = $policy->delete($authenticatedAdmin, $adminToDelete);

    // Then: deletion is allowed
    expect($response->allowed())->toBeTrue();
});

test('CP-06_EIF-20_QA2 - allows employee to be deleted by admin', function () {
    // Given: admin attempting to delete an employee
    $admin = User::factory()->withRole(UserRole::ADMIN)->create();
    $employee = User::factory()->withRole(UserRole::EMPLOYEE)->create();

    // When: the delete policy is evaluated
    $policy = new UserPolicy;
    $response = $policy->delete($admin, $employee);

    // Then: deletion is allowed (employee is not admin)
    expect($response->allowed())->toBeTrue();
});

test('CP-08_EIF-20_QA2 - denies deletion of last remaining admin user', function () {
    // Given: only one admin exists in the system
    $authenticatedAdmin = User::factory()->withRole(UserRole::ADMIN)->create();
    $lastAdmin = User::factory()->withRole(UserRole::ADMIN)->create();

    // Delete the first admin so only $lastAdmin remains
    User::whereNotIn('id', [$lastAdmin->id])->delete();

    // When: attempting to delete the last admin
    $policy = new UserPolicy;
    $response = $policy->delete($authenticatedAdmin, $lastAdmin);

    // Then: deletion is denied with admin-specific message
    expect($response->allowed())->toBeFalse();
    expect($response->message())->toContain('último administrador');
});
