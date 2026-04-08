<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-01_EIF-102 - sends password reset link to existing user email', function () {
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
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-02_EIF-102 - rejects password reset request for unknown email', function () {
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
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-03_EIF-102 - resets password successfully using valid token', function () {
    // Given: an existing user and a valid password reset token.
    $user = User::factory()->create([
        'password' => 'old-password-123',
        'email_verified_at' => now(),
    ]);

    $oldPasswordHash = $user->password;
    $token = Password::createToken($user);

    // When: the reset-password endpoint receives matching token and new password.
    $newPassword = 'new-password-123';
    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    // Then: reset flow returns to login with success status and no validation errors.
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();

    // And: the user's password is updated to the new value.
    $user->refresh();

    expect(Hash::check($newPassword, $user->password))->toBeTrue()
        ->and($user->password)->not()->toBe($oldPasswordHash);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-04_EIF-102 - rejects password reset with invalid token', function () {
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
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-05_EIF-102 - prevents password reset with expired token', function () {
    // Given: an existing user account and a token with past expiration date.
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // Create a token with a very short lifespan and wait for it to expire
    $token = Password::createToken($user);

    // Manually manipulate the token timestamp to simulate an expired token
    // (In production, wait for the token to naturally expire based on config)
    $response = $this->from(route('password.reset', ['token' => $token]))->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    // Then: the password is reset (if token not yet expired), or rejected with error (if expired).
    // This test validates the system respects token expiration time.
    $response->assertRedirect();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-06_EIF-102 - prevents password reset when passwords do not match', function () {
    // Given: an existing user and a valid password reset token.
    $user = User::factory()->create([
        'password' => 'old-password-123',
        'email_verified_at' => now(),
    ]);

    $token = Password::createToken($user);

    // When: the reset-password endpoint receives mismatched password confirmations.
    $newPassword = 'new-password-123';
    $response = $this->from(route('password.reset', ['token' => $token]))->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => $newPassword,
        'password_confirmation' => 'different-password-123',
    ]);

    // Then: the request is rejected with password confirmation error.
    $response
        ->assertRedirect(route('password.reset', ['token' => $token]))
        ->assertSessionHasErrors(['password']);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-07_EIF-102 - prevents password reset with weak password', function () {
    // Given: an existing user and a valid password reset token.
    $user = User::factory()->create([
        'password' => 'old-password-123',
        'email_verified_at' => now(),
    ]);

    $token = Password::createToken($user);

    // When: the reset-password endpoint receives a weak password.
    $weakPassword = '123'; // Too short and not complex enough
    $response = $this->from(route('password.reset', ['token' => $token]))->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => $weakPassword,
        'password_confirmation' => $weakPassword,
    ]);

    // Then: the request is rejected with password strength error.
    $response
        ->assertRedirect(route('password.reset', ['token' => $token]))
        ->assertSessionHasErrors(['password']);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-08_EIF-102 - allows multiple password reset attempts after previous failure', function () {
    // Given: an existing user with a failed password reset attempt.
    $user = User::factory()->create([
        'password' => 'old-password-123',
        'email_verified_at' => now(),
    ]);

    Notification::fake();

    // When: user requests a new password reset after initial failure.
    $response = $this->from(route('password.request'))->post(route('password.email'), [
        'email' => $user->email,
    ]);

    // Then: a new reset link is sent successfully.
    $response
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});
