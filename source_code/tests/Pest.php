<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Api\Webpage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function actingAsAdmin(): void
{
    test()->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());
}

function actingAsEmployee(): void
{
    test()->actingAs(User::factory()->withRole(UserRole::EMPLOYEE)->create());
}

/**
 * Helper function to log in as a specific user in browser tests.
 *
 * @param  mixed  $user  The user instance to log in as.
 * @param  mixed  $password  The password for the user.
 * @return Webpage The webpage instance after logging in, allowing for further interactions in the test.
 */
function loginAsUser($user, $password)
{
    $page = visit(route('login'))
        ->fill('#email', $user->email)
        ->fill('#password', $password)
        ->click('#login-button')
        ->assertSee('Ventas de Hoy');

    return $page;
}
