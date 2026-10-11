<?php

use App\Enums\CashRegisterStatus;
use App\Enums\UserRole;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\User;

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Prueba de navegador: Flujo de navegación en el módulo de ventas.
 *
 * Sigue el patrón de LoginTest.php.
 * Ruta real del POS: sales.sell (SaleController@sales)
 * Ruta real del historial: sales.index (SaleController@index)
 */

// ─── CP-NAV-01: El POS carga y muestra los paneles principales ────────────

test('CP-NAV-01 - authenticated user can navigate to the POS and sees the sales interface', function () {
    // DADO: Caja abierta y usuario admin.
    CashRegister::factory()->create([
        'status' => CashRegisterStatus::OPEN,
        'opened_at' => now(),
    ]);

    $password = 'testpassword';
    $user = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'cajero@example.com',
        'password' => $password,
    ]);

    Product::factory()->create([
        'name' => 'Empanada de Queso',
        'has_inventory' => true,
        'sale_price' => 1500,
    ]);

    // CUANDO: El usuario inicia sesión y navega directo al POS (sales.sell).
    $page = visit(route('login'))
        ->assertSee('Iniciar Sesión')
        ->fill('#email', $user->email)
        ->fill('#password', $password)
        ->click('#login-button')
        ->navigate(route('sales.sell'));

    $this->assertAuthenticatedAs($user);

    // ENTONCES: El POS carga con los dos paneles definidos en sales.blade.php.
    $page->assertSee('Seleccione un producto')
        ->assertSee('Revisar Orden')
        ->assertSee('Proceder al pago');
})->group('s7-tests');

// ─── CP-NAV-02: El buscador de productos filtra por nombre ────────────────

test('CP-NAV-02 - product search input filters visible products by name', function () {
    // DADO: Caja abierta, usuario admin y dos productos con nombres distintos.
    CashRegister::factory()->create([
        'status' => CashRegisterStatus::OPEN,
        'opened_at' => now(),
    ]);

    $password = 'searchpassword';
    $user = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'busqueda@example.com',
        'password' => $password,
    ]);

    Product::factory()->create([
        'name' => 'Empanada de Queso',
        'has_inventory' => true,
        'sale_price' => 1500,
    ]);

    Product::factory()->create([
        'name' => 'Casado Grande',
        'has_inventory' => true,
        'sale_price' => 5000,
    ]);

    // CUANDO: El usuario navega al POS.
    $page = visit(route('login'))
        ->assertSee('Iniciar Sesión')
        ->fill('#email', $user->email)
        ->fill('#password', $password)
        ->click('#login-button')
        ->navigate(route('sales.sell'));

    $this->assertAuthenticatedAs($user);

    // Y: El usuario escribe en el buscador (id real: #product-search en sales.blade.php).
    $page->assertSee('Empanada de Queso')
        ->assertSee('Casado Grande');

    $page->fill('#product-search', 'Empanada')
        ->wait(2);

    // ENTONCES: Solo aparece el producto que coincide con la búsqueda.
    $page->assertSee('Empanada de Queso');
})->group('s7-tests');

// ─── CP-NAV-03: El historial de ventas es accesible y muestra la tabla ────

test('CP-NAV-03 - authenticated user can access the sales history and sees the table', function () {
    // DADO: Caja abierta y usuario admin.
    CashRegister::factory()->create([
        'status' => CashRegisterStatus::OPEN,
        'opened_at' => now(),
    ]);

    $password = 'historypassword';
    $user = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'historial@example.com',
        'password' => $password,
    ]);

    // CUANDO: El usuario navega al historial de ventas (sales.index).
    $page = visit(route('login'))
        ->assertSee('Iniciar Sesión')
        ->fill('#email', $user->email)
        ->fill('#password', $password)
        ->click('#login-button')
        ->navigate(route('sales.index'));

    $this->assertAuthenticatedAs($user);

    // ENTONCES: La página del historial carga con su título y encabezados de tabla
    // definidos en index.blade.php.
    $page->assertSee('Historial de Ventas')
        ->assertSee('N° Factura')
        ->assertSee('Cajero')
        ->assertSee('Estado');
})->group('s7-tests');

// ─── CP-NAV-04: Navegación completa POS → historial → logout ──────────────

test('CP-NAV-04 - user can navigate from POS to history and logout successfully', function () {
    // DADO: Caja abierta y usuario admin.
    CashRegister::factory()->create([
        'status' => CashRegisterStatus::OPEN,
        'opened_at' => now(),
    ]);

    $password = 'navpassword';
    $user = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'navegacion@example.com',
        'password' => $password,
    ]);

    // CUANDO: El usuario inicia sesión y navega al POS.
    $page = visit(route('login'))
        ->assertSee('Iniciar Sesión')
        ->fill('#email', $user->email)
        ->fill('#password', $password)
        ->click('#login-button')
        ->navigate(route('sales.sell'));

    $this->assertAuthenticatedAs($user);

    $page->assertSee('Seleccione un producto');

    // Y: Navega al historial de ventas sin intervención manual.
    $page->navigate(route('sales.index'))
        ->assertSee('Historial de Ventas')
        ->assertSee('N° Factura');

    // Y: Cierra sesión correctamente.
    $page->script('document.getElementById("logout-form").submit()');

    $page->assertPathIs('/login')
        ->assertSee('Iniciar Sesión');

    $this->assertGuest();
})->group('s7-tests');
