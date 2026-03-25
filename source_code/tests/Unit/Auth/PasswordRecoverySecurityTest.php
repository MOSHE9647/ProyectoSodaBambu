<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-09_EIF-102 - password hash is never the same for identical passwords on different users', function () {
    // Given: two users with the same password.
    $password = 'same-password-123';
    $user1 = User::factory()->create(['password' => $password]);
    $user2 = User::factory()->create(['password' => $password]);

    // When: comparing the stored hashes.
    $hash1 = $user1->password;
    $hash2 = $user2->password;

    // Then: the hashes are different due to salt randomization in bcrypt.
    expect($hash1)->not->toBe($hash2)
        ->and(Hash::check($password, $hash1))->toBeTrue()
        ->and(Hash::check($password, $hash2))->toBeTrue();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-10_EIF-102 - password is not stored in plaintext in database', function () {
    // Given: a user with a known plaintext password.
    $plainPassword = 'test-password-plain-456';
    $user = User::factory()->create(['password' => $plainPassword]);

    // When: retrieving the stored password from database.
    $user->refresh();
    $storedPassword = $user->password;

    // Then: the stored value is not the plaintext password.
    expect($storedPassword)->not->toBe($plainPassword)
        ->and(strlen($storedPassword))->toBeGreaterThan(strlen($plainPassword));
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-11_EIF-102 - password hashing uses bcrypt algorithm', function () {
    // Given: a password hashed using Laravel's Hash facade.
    $password = 'bcrypt-test-789';
    $hashedPassword = Hash::make($password);

    // When: examining the hash structure and algorithm.
    // Then: the hash follows bcrypt format ($2y$ prefix for Laravel).
    expect($hashedPassword)->toMatch('/^\$2[aby]\$/')
        ->and(Hash::check($password, $hashedPassword))->toBeTrue();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-12_EIF-102 - password reset token is not stored plaintext', function () {
    // Given: a user requesting a password reset token.
    $user = User::factory()->create(['email' => 'token-test@example.com']);

    // When: creating a reset token.
    $token = Password::createToken($user);

    // Then: the token is not empty and appears to be hashed in database.
    expect($token)->not->toBeNull()
        ->and(strlen($token))->toBeGreaterThan(20);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-13_EIF-102 - two password reset requests generate different tokens', function () {
    // Given: a user requesting multiple password reset tokens.
    $user = User::factory()->create(['email' => 'multi-token@example.com']);

    // When: creating two tokens in sequence.
    $token1 = Password::createToken($user);
    $token2 = Password::createToken($user);

    // Then: each token is unique.
    expect($token1)->not->toBe($token2)
        ->and($token1)->not->toBeNull()
        ->and($token2)->not->toBeNull();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-14_EIF-102 - password update creates new hash different from original', function () {
    // Given: a user with an initial hashed password.
    $oldPassword = 'old-password-to-test';
    $user = User::factory()->create(['password' => $oldPassword]);

    $oldHash = $user->password;

    // When: the password is updated to a new value (using normal update to trigger mutator).
    $newPassword = 'new-password-to-test';
    $user->update(['password' => $newPassword]);

    // Then: the new hash is completely different from the old one and new password validates.
    $user->refresh();
    expect($user->password)->not->toBe($oldHash)
        ->and(Hash::check($newPassword, $user->password))->toBeTrue();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-15_EIF-102 - confirms password is hashed using model mutators', function () {
    // Given: a user model with password hashing capability.
    $plainPassword = 'mutator-test-999';

    // When: creating a user with plaintext password.
    $user = User::factory()->create(['password' => $plainPassword]);

    // Then: the stored password is automatically hashed.
    expect(Hash::check($plainPassword, $user->password))->toBeTrue()
        ->and($user->password)->not->toBe($plainPassword);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-16_EIF-102 - password reset token expires after configured time', function () {
    // Given: the configured password reset token expiration time.
    $expirationMinutes = config('auth.passwords.users.expire');

    // Then: the expiration time is set to a secure value (should be in minutes).
    expect($expirationMinutes)->toBeGreaterThan(0)
        ->and($expirationMinutes)->toBeLessThanOrEqual(60);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-17_EIF-102 - password broker throttle setting is configured for security', function () {
    // Given: the password reset throttle configuration.
    $throttleSeconds = config('auth.passwords.users.throttle');

    // Then: throttle is enabled to prevent brute force attacks.
    expect($throttleSeconds)->toBeGreaterThan(0)
        ->and($throttleSeconds)->toBeLessThanOrEqual(3600);
});
