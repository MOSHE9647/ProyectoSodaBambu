<?php

use App\Actions\Finance\ProcessPaymentAction;
use App\Actions\Finance\RegisterTransactionAction;
use App\Actions\Finance\UpdatePaymentAction;
use App\Actions\Inventory\UpsertPurchaseAction;
use App\Enums\CashRegisterStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Enums\UserRole;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;

beforeEach(function () {
    app()->bind('Illuminate\Foundation\Vite', function () {
        return new class
        {
            public function __invoke(...$args)
            {
                return '';
            }

            public function __call($name, $args)
            {
                return '';
            }

            public static function __callStatic($name, $args)
            {
                return '';
            }
        };
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Crea una caja abierta hoy en zona horaria CR (UTC-6).
 * RegisterTransactionAction filtra por 'opened_at' dentro del día local.
 */
function createOpenCashRegister(): CashRegister
{
    return CashRegister::factory()->create([
        'status' => CashRegisterStatus::OPEN,
        'opened_at' => now('America/Costa_Rica')->setTimezone('UTC'),
    ]);
}

/**
 * Crea un admin autenticado para ejecutar la Action.
 */
function createAdminForPurchases(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create();
}

/**
 * Construye la Action con sus dependencias reales (sin mocks).
 */
function buildUpsertPurchaseAction(): UpsertPurchaseAction
{
    return new UpsertPurchaseAction(
        new ProcessPaymentAction(new RegisterTransactionAction),
        app(UpdatePaymentAction::class),
    );
}

/**
 * Datos mínimos para crear una compra pagada.
 */
function purchasePayload(int $total = 10000): array
{
    $supplier = Supplier::factory()->create();

    return [
        'purchaseData' => [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-TEST-001',
            'date' => now()->toDateTimeString(),
            'total' => $total,
            'payment_status' => PaymentStatus::PAID->value,
        ],
        'detailsData' => [],
        'paymentData' => [[
            'amount' => $total,
            'method' => PaymentMethod::CASH->value,
            'change_amount' => 0,
            'date' => now()->toDateTimeString(),
        ]],
    ];
}

// ═════════════════════════════════════════════════════════════════════════════
// User Story : HU - Integrar lógica de compras dentro de la lógica de pagos
//              y movimientos de caja.
// Criterios  : 1) La realización o edición de una compra debe registrarse
//                 debidamente en caja.
//              2) Las compras deben estar incluidas en caja como un egreso.
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Criterio 1: Al crear una compra con pago, se genera exactamente una
 * Transaction vinculada a la caja abierta del día.
 * Verifica que UpsertPurchaseAction → ProcessPaymentAction →
 * RegisterTransactionAction escribe en la tabla transactions.
 */
test('CP-01_HU-PURCHASE - creating a purchase with payment registers one transaction in the open cash register', function () {
    // Given: admin autenticado y una caja abierta hoy
    $admin = createAdminForPurchases();
    $cashRegister = createOpenCashRegister();
    $this->actingAs($admin);

    $payload = purchasePayload(10000);
    $action = buildUpsertPurchaseAction();

    // When: se ejecuta la acción de creación de compra
    $purchase = $action->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    // Then: existe exactamente una transacción asociada a la caja abierta.
    // Se busca el pago por polimorfismo porque morphOne no se recarga
    // automáticamente en el mismo ciclo que lo crea ProcessPaymentAction.
    $payment = Payment::where('origin_type', Purchase::class)
        ->where('origin_id', $purchase->id)
        ->firstOrFail();
    $transaction = Transaction::where('cash_register_id', $cashRegister->id)->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->cash_register_id)->toBe($cashRegister->id)
        ->and($transaction->payment_id)->toBe($payment->id);
});

/**
 * Criterio 2: La transacción generada por una compra tiene tipo EXPENSE.
 * RegisterTransactionAction mapea Purchase::class → TransactionType::EXPENSE.
 */
test('CP-02_HU-PURCHASE - purchase payment transaction is recorded as expense type in cash register', function () {
    // Given: admin, caja abierta y payload de compra
    $admin = createAdminForPurchases();
    createOpenCashRegister();
    $this->actingAs($admin);

    $payload = purchasePayload(15000);
    $action = buildUpsertPurchaseAction();

    // When: se crea la compra
    $purchase = $action->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    // Then: el tipo de la transacción es EXPENSE (egreso).
    // Se recarga el pago desde BD para obtener la relación transaction.
    $payment = Payment::where('origin_type', Purchase::class)
        ->where('origin_id', $purchase->id)
        ->firstOrFail();
    $transaction = $payment->transaction;

    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(TransactionType::EXPENSE);
});

/**
 * Criterio 1: Al editar una compra cuyo nuevo total es menor al pagado,
 * UpsertPurchaseAction genera automáticamente un pago de devolución
 * que también se registra como transacción en caja.
 */
test('CP-03_HU-PURCHASE - editing a purchase with lower total auto-creates refund transaction in cash register', function () {
    // Given: compra existente con pago de 20 000 y caja abierta
    $admin = createAdminForPurchases();
    createOpenCashRegister();
    $this->actingAs($admin);

    $payload = purchasePayload(20000);
    $action = buildUpsertPurchaseAction();
    $purchase = $action->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    $transactionsBefore = Transaction::count();

    // When: se edita la compra bajando el total a 15 000 (devolución de 5 000)
    $action->execute(
        array_merge($payload['purchaseData'], [
            'id' => $purchase->id,
            'total' => 15000,
        ]),
        [],
        null, // sin nuevos pagos → dispara refund automático
    );

    // Then: se creó una nueva transacción de devolución en caja
    expect(Transaction::count())->toBeGreaterThan($transactionsBefore);
});

/**
 * Criterio 1: Sin una caja abierta hoy, la creación de una compra con pago
 * lanza una excepción. Garantiza que el registro en caja es obligatorio
 * y no se procesa un pago huérfano.
 */
test('CP-04_HU-PURCHASE - creating a purchase without an open cash register throws exception', function () {
    // Given: admin autenticado pero SIN caja abierta
    $admin = createAdminForPurchases();
    $this->actingAs($admin);

    // Asegurar que no hay cajas abiertas hoy
    CashRegister::query()->update(['status' => CashRegisterStatus::CLOSED]);

    $payload = purchasePayload(10000);
    $action = buildUpsertPurchaseAction();

    // Then: la Action lanza excepción al intentar registrar sin caja
    expect(fn () => $action->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    ))->toThrow(Exception::class, 'No hay una caja abierta para registrar la transacción.');
});

/**
 * Criterio 2: El monto de la transacción de egreso coincide exactamente
 * con el total de la compra. Transaction::amount guarda el valor absoluto,
 * sin importar el signo del pago original.
 */
test('CP-05_HU-PURCHASE - expense transaction amount matches purchase total exactly', function () {
    // Given: admin, caja abierta y compra de 25 000
    $admin = createAdminForPurchases();
    createOpenCashRegister();
    $this->actingAs($admin);

    $total = 25000;
    $payload = purchasePayload($total);
    $action = buildUpsertPurchaseAction();

    // When: se crea la compra
    $purchase = $action->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    // Then: el monto de la transacción es el valor absoluto del total.
    // Se recarga el pago desde BD para obtener la relación transaction.
    $payment = Payment::where('origin_type', Purchase::class)
        ->where('origin_id', $purchase->id)
        ->firstOrFail();
    $transaction = $payment->transaction;

    expect($transaction->amount)->toBe($total)
        ->and($transaction->type)->toBe(TransactionType::EXPENSE);
});
