<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    app()->bind('Illuminate\Foundation\Vite', function () {
        return new class {
            public function __invoke(...$args) { return ''; }
            public function __call($name, $args) { return ''; }
            public static function __callStatic($name, $args) { return ''; }
        };
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Helper
// ─────────────────────────────────────────────────────────────────────────────

function createAdminForSales(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create();
}

// ═════════════════════════════════════════════════════════════════════════════
// User Story : HU - Cambiar modal de pago de SweetAlert a Bootstrap
// Criterios  : 1) Usar modal Bootstrap en lugar de SweetAlert.
//              2) Estilos del modal coinciden con los de SweetAlert.
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Criterio 1: La vista de ventas carga correctamente y contiene
 * el botón de "Proceder al pago" que activa el modal de Bootstrap.
 * Verifica que el punto de entrada al flujo del modal existe en el DOM.
 */
test('CP-01_HU-MODAL - sales view renders finalize sale button that triggers bootstrap modal', function () {
    // Given: un admin autenticado accede a la página de ventas
    $admin = createAdminForSales();

    // When: se carga la vista de ventas
    $response = $this->actingAs($admin)->get(route('sales.sell'));

    // Then: la vista carga con éxito y contiene el botón de pago con su ID correcto
    $response->assertSuccessful()
        ->assertViewIs('pages.sales.sales')
        ->assertSee('finalize-sale-button', false)
        ->assertSee('finalize-sale-spinner', false);
});

/**
 * Criterio 1: La vista de ventas NO incluye referencias directas a
 * SweetAlert en el HTML renderizado para el flujo de pago.
 * El modal de pago ahora es responsabilidad de Bootstrap.
 */
test('CP-02_HU-MODAL - sales view does not render swal2 modal markup for payment flow', function () {
    // Given: admin autenticado
    $admin = createAdminForSales();

    // When: se carga la vista de ventas
    $response = $this->actingAs($admin)->get(route('sales.sell'));

    // Then: el HTML de la vista no contiene clases propias del popup de SweetAlert
    $response->assertSuccessful()
        ->assertDontSee('swal2-popup', false)
        ->assertDontSee('swal2-modal', false);
});

/**
 * Criterio 2: El blade del modal de pago contiene el ID del formulario
 * 'payment-details-form' y los IDs de los elementos de estado de carga
 * (spinner y botón) que utiliza setLoadingState() de utils.js.
 * Garantiza que los estilos de carga son consistentes con el resto del sistema.
 */
test('CP-03_HU-MODAL - payment modal blade contains form and loading state element IDs used by utils setLoadingState', function () {
    // Given: admin autenticado que accede al modal via la ruta de pago
    $admin = createAdminForSales();

    $response = $this->actingAs($admin)->get(
        route('receipts.payment-modal', ['paymentTotal' => 10000])
    );

    // Then: el blade del modal contiene los IDs críticos para el flujo de Bootstrap
    $response->assertSuccessful()
        ->assertSee('payment-details-form', false)   // formulario principal
        ->assertSee('payment-button', false)          // botón de submit
        ->assertSee('payment-spinner', false)         // spinner de carga
        ->assertSee('payment-button-text', false);    // texto del botón
});

/**
 * Criterio 2: El blade del modal renderiza los métodos de pago (Efectivo,
 * Tarjeta, SINPE) con sus IDs correctos, manteniendo la misma estructura
 * visual que tenía el flujo de SweetAlert.
 */
test('CP-04_HU-MODAL - payment modal blade renders all three payment method options with correct ids', function () {
    // Given: admin autenticado
    $admin = createAdminForSales();

    $response = $this->actingAs($admin)->get(
        route('receipts.payment-modal', ['paymentTotal' => 5000])
    );

    // Then: los tres métodos de pago están presentes con sus IDs y labels
    $response->assertSuccessful()
        ->assertSee('cash_payment', false)
        ->assertSee('card_payment', false)
        ->assertSee('sinpe_payment', false)
        ->assertSee('Efectivo')
        ->assertSee('Tarjeta')
        ->assertSee('SINPE Móvil');
});

/**
 * Criterio 2: El total a pagar que se muestra en el modal usa format_crc
 * y se inyecta en el elemento con id 'total_amount', asegurando que
 * el estilo numérico del modal Bootstrap coincide con el que mostraba SweetAlert.
 */
test('CP-05_HU-MODAL - payment modal blade displays formatted total amount in total_amount element', function () {
    // Given: admin autenticado y un total de pago conocido
    $admin = createAdminForSales();
    $paymentTotal = 15000;

    $response = $this->actingAs($admin)->get(
        route('receipts.payment-modal', ['paymentTotal' => $paymentTotal])
    );

    // Then: el monto formateado aparece dentro del elemento total_amount
    $response->assertSuccessful()
        ->assertSee('total_amount', false)
        ->assertSee('15 000');          // format_crc(15000, false) → "15 000"
});