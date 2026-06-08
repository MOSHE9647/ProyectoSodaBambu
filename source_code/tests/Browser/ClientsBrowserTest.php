<?php

use App\Enums\UserRole;
use App\Models\User;

/**
 * User Story: CP-BROWSER-01 - Navegación al módulo de clientes y creación desde el navegador.
 * Epic: EIF-24_QA1 - Gestión de Clientes
 * Priority: High
 *
 * Patrón: sigue la misma estructura que LoginTest.php (tests/Browser/LoginTest.php).
 * Valida: entrar al módulo, crear un cliente mediante el formulario y volver al listado.
 */
test('CP-BROWSER-01 - admin can enter the clients module, create a client and return to the listing', function () {
    // Given: an administrator user exists in the database.
    $adminPassword = 'adminpassword';
    $adminUser = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'admin@example.com',
        'password' => $adminPassword,
    ]);

    // When: the administrator logs in.
    // 'Inicio' is used instead of the default 'Ventas de Hoy' because the dashboard cards
    // load dynamic data (charts/DB queries) that may exceed the default 5s timeout on cold start.
    $page = loginAsUser($adminUser, $adminPassword, 'Inicio');
    $this->assertAuthenticatedAs($adminUser);

    // And: navigates to the clients module.
    $page->navigate(route('clients.index'));
    $page->assertSee('Gestión de Clientes')
        ->wait(10) // Wait for the DataTable and the dynamic create button to render
        ->click('.create-button')
        ->assertSee('Crear Cliente');

    // And: fills the client creation form with valid data.
    // Phone must be a valid Costa Rican number (+506 XXXX XXXX) because the JS validator
    // (validateAndDisplayField) marks ANY empty field in fieldValidators as invalid,
    // which would block the native form submit via e.currentTarget.submit().
    $newClient = [
        'first_name' => 'María',
        'last_name' => 'García',
        'email' => 'maria.garcia@example.com',
        'phone' => '+506 8765 4321',
    ];

    $page->fill('#first_name', $newClient['first_name'])
        ->fill('#last_name', $newClient['last_name'])
        ->fill('#email', $newClient['email'])
        ->fill('#phone', $newClient['phone'])
        ->click('#create-client-form-button');

    // Then: the success message is shown and the listing displays the new client.
    $page->assertSee('Cliente creado correctamente.')
        ->select('#dt-length-0', 'All')
        ->assertSee($newClient['email']);

    // And: the client is persisted in the database with the correct data.
    $this->assertDatabaseHas('clients', [
        'first_name' => $newClient['first_name'],
        'email' => $newClient['email'],
    ]);

    // And: the administrator logs out and is redirected to the login page.
    $page->script('document.getElementById("logout-form").submit()');
    $page->assertPathIs('/login')->assertSee('Iniciar Sesión');
    $this->assertGuest();
})->group('s7-tests');
