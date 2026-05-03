<?php

use App\Enums\UserRole;
use App\Models\User;

test('CP-01_EIF-02: Iniciar sesión con credenciales válidas', function () {
    // Given: un usuario existente con credenciales válidas.
    $password = 'password123';
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => $password,
    ]);

    // When: el usuario intenta iniciar sesión con las credenciales correctas.
    $page = visit(route('login'));

    // Then: el usuario es autenticado y redirigido a la página de inicio.
    $page->assertSee('Iniciar Sesión')
        ->fill('#email', $user->email)
        ->fill('#password', $password)
        ->click('#login-button')
        ->assertSee('Ventas de Hoy');

    $this->assertAuthenticated();
});

test('CP-02_EIF-02: Un usuario administrador con sesión iniciada puede crear un usuario', function () {
    // Given: un usuario administrador con sesión iniciada.
    $adminPassword = 'adminpassword';
    $adminUser = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'admin@example.com',
        'password' => $adminPassword,
    ]);

    // When: el usuario administrador inicia sesión.
    $page = loginAsUser($adminUser, $adminPassword);
    $this->assertAuthenticatedAs($adminUser);

    // And: el usuario administrador puede acceder a la página de creación de usuarios.
    $page->navigate(route('users.index'));
    $page->assertSee('Gestión de Usuarios')
        ->click('.create-button')
        ->assertSee('Crear Usuario');

    // And: el usuario administrador completa el formulario de creación de usuario y lo envía.
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

    // Then: el nuevo usuario es creado exitosamente y aparece en la lista de usuarios.
    $page->assertSee('Usuario creado correctamente.')
        ->select('#dt-length-0', 'All')
        ->assertSee($newUser['email']);

    // And: el usuario existe en la base de datos con los datos correctos.
    $this->assertDatabaseHas('users', [
        'email' => $newUser['email'],
        'name' => $newUser['name'],
    ]);
});
