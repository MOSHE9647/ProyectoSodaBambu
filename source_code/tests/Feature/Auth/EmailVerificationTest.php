<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function createUnverifiedEmployeeUser(string $password = 'password123'): User
{
    return User::factory()->withRole(UserRole::EMPLOYEE)->create([
        'password' => $password,
        'email_verified_at' => null,
    ]);
}

/**
 * Epic: EIF-20_QA3 - Email verification flow (Internal QA Story).
 * Note: Password recovery tests have been moved to PasswordRecoveryFlowTest.php (EIF-102)
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-01_EIF-20_QA3 - allows unverified user to request a verification email', function () {
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
 * Epic: EIF-20_QA3 - Email verification flow (Internal QA Story).
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-02_EIF-20_QA3 - verifies email when user opens a valid signed verification link', function () {
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
