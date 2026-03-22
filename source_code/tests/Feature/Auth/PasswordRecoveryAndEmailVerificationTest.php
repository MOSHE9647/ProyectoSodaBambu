<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

function createUnverifiedEmployeeUser(string $password = 'password123'): User
{
    return User::factory()->withRole(UserRole::EMPLOYEE)->create([
        'password' => $password,
        'email_verified_at' => null,
    ]);
}

/**
 * User Story: EIF-903 - Password recovery and email verification flows (Internal QA Story, pending Jira creation).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-903
 */
test('CP-01_EIF-903 - sends password reset link to existing user email', function () {
    // Given: an existing user account with a valid email address.
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Notification::fake();

    // When: the forgot-password form is submitted with that email.
    $response = $this->from(route('password.request'))->post(route('password.email'), [
        'email' => $user->email,
    ]);

    // Then: the app redirects back with status and dispatches reset notification.
    $response
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

/**
 * User Story: EIF-903 - Password recovery and email verification flows (Internal QA Story, pending Jira creation).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-903
 */
test('CP-02_EIF-903 - rejects password reset request for unknown email', function () {
    // Given: an email that does not belong to any user.

    // When: the forgot-password endpoint receives that unknown email.
    $response = $this->from(route('password.request'))->post(route('password.email'), [
        'email' => 'missing.account@example.com',
    ]);

    // Then: the flow returns to form and reports validation error on email.
    $response
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['email']);
});

/**
 * User Story: EIF-903 - Password recovery and email verification flows (Internal QA Story, pending Jira creation).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-903
 */
test('CP-03_EIF-903 - resets password successfully using valid token', function () {
    // Given: an existing user and a valid password reset token.
    $user = User::factory()->create([
        'password' => 'old-password-123',
        'email_verified_at' => now(),
    ]);

    $token = Password::broker()->createToken($user);

    // When: the reset-password endpoint receives matching token and new password.
    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    // Then: reset flow returns to login with success status and no validation errors.
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();
});

/**
 * User Story: EIF-903 - Password recovery and email verification flows (Internal QA Story, pending Jira creation).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-903
 */
test('CP-04_EIF-903 - rejects password reset with invalid token', function () {
    // Given: an existing user account and an invalid reset token.
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // When: password update is requested with an invalid token value.
    $response = $this->from(route('password.reset', ['token' => 'invalid-token']))->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    // Then: the request is rejected with an email-related error.
    $response
        ->assertRedirect(route('password.reset', ['token' => 'invalid-token']))
        ->assertSessionHasErrors(['email']);
});

/**
 * User Story: EIF-903 - Password recovery and email verification flows (Internal QA Story, pending Jira creation).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-903
 */
test('CP-05_EIF-903 - allows unverified user to request a verification email', function () {
    // Given: an authenticated user whose email is not verified.
    $user = createUnverifiedEmployeeUser();

    Notification::fake();

    // When: the user requests a new verification email.
    $response = $this->actingAs($user)->post(route('verification.send'));

    // Then: request succeeds with status and verification notification is sent.
    $response
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});

/**
 * User Story: EIF-903 - Password recovery and email verification flows (Internal QA Story, pending Jira creation).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-903
 */
test('CP-06_EIF-903 - verifies email when user opens a valid signed verification link', function () {
    // Given: an authenticated unverified user and a valid signed verification URL.
    $user = createUnverifiedEmployeeUser();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    // When: the user opens the signed verification link.
    $response = $this->actingAs($user)->get($verificationUrl);

    // Then: email gets verified and the flow redirects successfully.
    $response->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
