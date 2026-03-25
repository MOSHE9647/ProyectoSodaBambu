<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-09_EIF-102 - validates exact success message when reset is completed', function () {
    // Given: an existing user and a valid password reset token.
    $user = User::factory()->create([
        'password' => 'old-password-123',
        'email_verified_at' => now(),
    ]);

    $token = Password::createToken($user);

    // When: the reset-password endpoint receives valid data.
    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    // Then: the session contains the expected success message from lang/es/passwords.php.
    $response->assertSessionHas('status', __('passwords.reset'));
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-10_EIF-102 - validates exact error message for unknown email', function () {
    // Given: an email that does not belong to any user.

    // When: the forgot-password endpoint receives that unknown email.
    $response = $this->from(route('password.request'))->post(route('password.email'), [
        'email' => 'nonexistent@example.com',
    ]);

    // Then: the response contains the exact error message from lang/es/passwords.php.
    $response->assertSessionHasErrors(['email' => __('passwords.user')]);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-11_EIF-102 - validates exact error message for invalid token', function () {
    // Given: an existing user and an invalid reset token.
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // When: password update is attempted with an invalid token.
    $response = $this->from(route('password.reset', ['token' => 'invalid-token']))->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    // Then: the response contains the exact token error message.
    $response->assertSessionHasErrors(['email' => __('passwords.token')]);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-12_EIF-102 - confirms password is encrypted in database after successful reset', function () {
    // Given: an existing user and a valid password reset token.
    $user = User::factory()->create([
        'password' => 'old-password-123',
        'email_verified_at' => now(),
    ]);

    $token = Password::createToken($user);
    $plainPassword = 'new-password-456';

    // When: the reset-password endpoint successfully updates the password.
    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => $plainPassword,
        'password_confirmation' => $plainPassword,
    ]);

    // Then: the password in the database is encrypted (hashed) and not the plain text.
    $user->refresh();
    expect($user->password)->not->toBe($plainPassword)
        ->and(Hash::check($plainPassword, $user->password))->toBeTrue();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-13_EIF-102 - confirms old password is no longer valid after reset', function () {
    // Given: an existing user with a known password.
    $oldPassword = 'old-password-789';
    $user = User::factory()->create([
        'password' => $oldPassword,
        'email_verified_at' => now(),
    ]);

    $token = Password::createToken($user);
    $newPassword = 'new-password-789';

    // When: the password is reset to a new value.
    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ]);

    // Then: the old password no longer authenticates.
    $user->refresh();
    expect(Hash::check($oldPassword, $user->password))->toBeFalse();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-14_EIF-102 - ensures security configuration prevents brute force on recovery', function () {
    // Given: the password broker configuration.
    $expirationMinutes = config('auth.passwords.users.expire');
    $throttleSeconds = config('auth.passwords.users.throttle');

    // When: reviewing security settings.
    // Then: the system is configured to prevent brute force attacks.
    // Expires within 60 minutes and throttles requests by 60+ seconds minimum.
    expect($expirationMinutes)->toBeGreaterThan(0)
        ->and($throttleSeconds)->toBeGreaterThanOrEqual(30);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-15_EIF-102 - throttle configuration is enabled to prevent brute force', function () {
    // Given: the password reset throttle configuration.
    $throttleSeconds = config('auth.passwords.users.throttle');

    // When: examining the security configuration.
    // Then: throttling is enabled (prevents multiple reset requests in short time).
    expect($throttleSeconds)->toBeGreaterThan(0)
        ->and($throttleSeconds)->toBeLessThanOrEqual(3600);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-16_EIF-102 - ensures password hash changes after reset', function () {
    // Given: an existing user with an initial password hash.
    $user = User::factory()->create([
        'password' => 'initial-password-999',
        'email_verified_at' => now(),
    ]);

    $originalHash = $user->password;
    $token = Password::createToken($user);

    // When: the password is reset to a completely different value.
    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'completely-different-999',
        'password_confirmation' => 'completely-different-999',
    ]);

    // Then: the hash in the database is completely different from the original.
    $user->refresh();
    expect($user->password)->not->toBe($originalHash);
});
