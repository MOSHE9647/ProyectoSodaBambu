<?php

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

function createVerifiedUser(UserRole $role, string $password = 'password123'): User
{
    return User::factory()->withRole($role)->create([
        'password' => $password,
        'email_verified_at' => now(),
    ]);
}

/**
 * Epic: EIF-20_QA1 - Authentication and role-based entry access (Internal QA Story).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-01_EIF-20_QA1 - redirects guest users from home route to login page', function () {
    // Given: a guest user without an authenticated session.

    // When: the guest accesses the home route.
    $response = $this->get(route('home'));

    // Then: the request is redirected to the login page.
    $response->assertRedirect(route('login'));
});

/**
 * Epic: EIF-20_QA1 - Authentication and role-based entry access (Internal QA Story).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-02_EIF-20_QA1 - logs in an admin and redirects to dashboard through home route', function () {
    // Given: a verified admin account with valid credentials.
    $admin = createVerifiedUser(UserRole::ADMIN);

    // When: the admin submits valid login credentials.
    $response = $this->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'password123',
    ]);

    // Then: authentication succeeds and the flow redirects through home.
    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($admin);

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertRedirect(route('dashboard'));
});

/**
 * Epic: EIF-20_QA1 - Authentication and role-based entry access (Internal QA Story).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-03_EIF-20_QA1 - rejects login when email does not exist', function () {
    // Given: a login attempt using an email not present in the system.

    // When: credentials are submitted to the login endpoint.
    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => 'unknown.user@example.com',
        'password' => 'password123',
    ]);

    // Then: the user is redirected back with email-specific validation error.
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

/**
 * Epic: EIF-20_QA1 - Authentication and role-based entry access (Internal QA Story).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-04_EIF-20_QA1 - rejects login when password is incorrect', function () {
    // Given: a verified admin account with a wrong password input.
    $admin = createVerifiedUser(UserRole::ADMIN);

    // When: the user submits correct email and incorrect password.
    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ]);

    // Then: authentication fails and password validation error is returned.
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['password']);

    $this->assertGuest();
});

/**
 * Epic: EIF-20_QA1 - Authentication and role-based entry access (Internal QA Story).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-05_EIF-20_QA1 - prevents inactive employees from logging in', function () {
    // Given: a verified employee account marked as inactive in employee profile.
    $employeeUser = createVerifiedUser(UserRole::EMPLOYEE);

    Employee::factory()->create([
        'id' => $employeeUser->id,
        'status' => EmployeeStatus::INACTIVE,
        'payment_frequency' => PaymentFrequency::MONTHLY,
        'hourly_wage' => 1800,
    ]);

    // When: the inactive employee submits valid credentials.
    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => $employeeUser->email,
        'password' => 'password123',
    ]);

    // Then: login is blocked with validation error and no session is authenticated.
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email']);

    $this->assertGuest();
});

/**
 * Epic: EIF-20_QA1 - Authentication and role-based entry access (Internal QA Story).
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-06_EIF-20_QA1 - redirects employee users from home route to sales module', function () {
    // Given: an authenticated and verified employee user.
    $employeeUser = createVerifiedUser(UserRole::EMPLOYEE);

    // When: the employee accesses the home route.
    $response = $this->actingAs($employeeUser)->get(route('home'));

    // Then: the user is redirected to the sales screen.
    $response->assertRedirect(route('sales'));
});
