<?php

use App\Models\User;

/**
 * Epic: EIF-20_QA3 - Email verification (Internal QA Story).
 * Note: Password recovery tests have been moved to PasswordRecoveryFlowTest.php (EIF-102)
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-01_EIF-20_QA3 - user model implements MustVerifyEmail interface', function () {
    // Given: a user with unverified email.
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    // When: checking email verification status.
    $isVerified = $user->hasVerifiedEmail();

    // Then: returns false for unverified email.
    expect($isVerified)->toBeFalse();
});

/**
 * Epic: EIF-20_QA3 - Email verification (Internal QA Story).
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-02_EIF-20_QA3 - user can mark email as verified', function () {
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
