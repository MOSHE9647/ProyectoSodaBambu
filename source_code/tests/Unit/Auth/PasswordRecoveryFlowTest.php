<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-01_EIF-102 - generates valid password reset token via password broker', function () {
    // Given: a user with a valid email address.
    $user = User::factory()->create(['email' => 'user@example.com']);

    // When: requesting password reset token generation.
    $token = Password::createToken($user);

    // Then: a valid reset token is generated and is string.
    expect($token)->not->toBeNull()
        ->and(is_string($token))->toBeTrue()
        ->and(strlen($token))->toBeGreaterThan(0);
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-02_EIF-102 - password broker tracks request for email', function () {
    // Given: a user email.
    $email = 'testuser@example.com';

    // When: password broker processes the email.
    $token = Password::createToken(
        User::factory()->create(['email' => $email])
    );

    // Then: token is generated for that email.
    expect($token)->not->toBeNull()
        ->and(is_string($token))->toBeTrue();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-03_EIF-102 - password hash verification works correctly', function () {
    // Given: a password hashed with bcrypt.
    $originalPassword = 'new-password-123';
    $hashedPassword = bcrypt($originalPassword);

    // When: verifying password against hash.
    $matches = Hash::check($originalPassword, $hashedPassword);

    // Then: verification succeeds.
    expect($matches)->toBeTrue();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-04_EIF-102 - password hash rejects incorrect password verification', function () {
    // Given: a stored bcrypt hash.
    $storedHash = bcrypt('correct-password');

    // When: verifying wrong password.
    $matches = Hash::check('wrong-password', $storedHash);

    // Then: verification fails.
    expect($matches)->toBeFalse();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-05_EIF-102 - password can be updated on user model', function () {
    // Given: a user with an initial password.
    $user = User::factory()->create(['password' => 'initial-password-123']);

    // When: updating the user password directly.
    $newPassword = 'new-password-456';
    $user->update(['password' => $newPassword]);

    // Then: password on database is the new hashed value.
    $user->refresh();
    expect(Hash::check($newPassword, $user->password))->toBeTrue();
});

/**
 * Bug: EIF-102 - Validación de Flujo de Recuperación de Contraseña (QA).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-102
 */
test('CP-06_EIF-102 - password reset token is unique per user', function () {
    // Given: two different users.
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);

    // When: requesting password reset tokens for both users.
    $token1 = Password::createToken($user1);
    $token2 = Password::createToken($user2);

    // Then: both tokens are generated and are different.
    expect($token1)->not->toBe($token2)
        ->and($token1)->not->toBeNull()
        ->and($token2)->not->toBeNull();
});
