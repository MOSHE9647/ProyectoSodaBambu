<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Unit Story: EIF-20_QA3 - Password recovery and email verification (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-01_EIF-20_QA3 - generates valid password reset token via password broker', function () {
    // Given: a user with a valid email address.
    $user = User::factory()->create(['email' => 'user@example.com']);

    // When: requesting password reset token generation.
    $token = Password::createToken($user);

    // Then: a valid reset token is generated and is string.
    expect($token)->not->toBeNull()
        ->and(is_string($token))->toBeTrue()
        ->and(strlen($token))->toBeGreaterThan(0);
});

test('CP-02_EIF-20_QA3 - password broker tracks request for email', function () {
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

test('CP-03_EIF-20_QA3 - password hash verification works correctly', function () {
    // Given: a password hashed with bcrypt.
    $originalPassword = 'new-password-123';
    $hashedPassword = bcrypt($originalPassword);

    // When: verifying password against hash.
    $matches = Hash::check($originalPassword, $hashedPassword);

    // Then: verification succeeds.
    expect($matches)->toBeTrue();
});

test('CP-04_EIF-20_QA3 - password hash rejects incorrect password verification', function () {
    // Given: a stored bcrypt hash.
    $storedHash = bcrypt('correct-password');

    // When: verifying wrong password.
    $matches = Hash::check('wrong-password', $storedHash);

    // Then: verification fails.
    expect($matches)->toBeFalse();
});

test('CP-05_EIF-20_QA3 - user model implements MustVerifyEmail interface', function () {
    // Given: a user with unverified email.
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    // When: checking email verification status.
    $isVerified = $user->hasVerifiedEmail();

    // Then: returns false for unverified email.
    expect($isVerified)->toBeFalse();
});

test('CP-06_EIF-20_QA3 - user can mark email as verified', function () {
    // Given: a user with unverified email.
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    // When: marking email as verified.
    $user->markEmailAsVerified();

    // Then: email is marked as verified.
    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});
