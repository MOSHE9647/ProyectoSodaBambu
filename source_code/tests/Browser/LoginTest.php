<?php

use App\Enums\UserRole;
use App\Models\User;

test('CP-FUN-03 - an administrator with an active session can create a user', function () {
    // Given: an administrator user exists in the database.
    $adminPassword = 'adminpassword';
    $adminUser = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'admin@example.com',
        'password' => $adminPassword,
    ]);

    // When: the administrator user logs in.
    $page = loginAsUser($adminUser, $adminPassword);
    $this->assertAuthenticatedAs($adminUser);

    // And: the administrator user can access the user creation page.
    $page->navigate(route('users.index'));
    $page->assertSee('Gestión de Usuarios')
        ->wait(10) // Wait for the page to load and the create button to be visible
        ->click('.create-button')
        ->assertSee('Crear Usuario');

    // And: the administrator user completes the user creation form and submits it.
    $newUser = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'newpassword123',
    ];

    $page->fill('#name', $newUser['name'])
        ->fill('#email', $newUser['email'])
        ->fill('#password', $newUser['password'])
        ->fill('#password_confirmation', $newUser['password'])
        ->select('#role', UserRole::ADMIN->value)
        ->click('#create-user-form-button');

    // Then: the new user is created successfully and appears in the users list.
    $page->assertSee('Usuario creado correctamente.')
        ->select('#dt-length-0', 'All')
        ->assertSee($newUser['email']);

    // And: the user exists in the database with the correct data.
    $this->assertDatabaseHas('users', [
        'email' => $newUser['email'],
        'name' => $newUser['name'],
    ]);

    // And: the administrator user can log out successfully.
    $page->script('document.getElementById("logout-form").submit()');

    $page->assertPathIs('/login')
        ->assertSee('Iniciar Sesión');
    $this->assertGuest();
})->group('s7-tests');
