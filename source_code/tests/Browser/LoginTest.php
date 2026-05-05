<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('CP-01_EIF-02 - creates a user logs in uses a feature and logs out', function () {
    // Dado que: se crea un usuario administrador con credenciales válidas.
    $password = 'password123';
    $user = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'test@example.com',
        'password' => $password,
        'email_verified_at' => now(),
    ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);

    // Cuando: el usuario abre la pantalla de login, digita sus credenciales y presiona ingresar.
    $page = visit(route('login'));

    $page->assertSee('Iniciar sesión')
        ->fill('#email', $user->email)
        ->fill('#password', $password)
        ->click('#login-button');

    // Entonces: el sistema autentica al usuario y muestra el dashboard.
    $page->assertPathIs(route('dashboard', absolute: false))
        ->assertSee('Ventas de hoy');

    $this->assertAuthenticatedAs($user);

    // Y: el usuario utiliza una funcionalidad del sistema al consultar la gestión de productos.
    $page->navigate(route('products.index'))
        ->assertPathIs(route('products.index', absolute: false))
        ->assertSee('Gestión de Productos')
        ->assertSee('Precio Venta');

    // Y: el usuario cierra sesión desde el menú de usuario.
    $page->click('.dropdown-toggle')
        ->click('#logoutBtn');

    $page->assertPathIs(route('login', absolute: false))
        ->assertSee('Iniciar sesión');

    $this->assertGuest();
});
