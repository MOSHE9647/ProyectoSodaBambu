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
        return new class {
            public function __invoke(...$args) { return ''; }
            public function __call($name, $args) { return ''; }
            public static function __callStatic($name, $args) { return ''; }
        };
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (duplicados localmente para independencia del archivo)
// ─────────────────────────────────────────────────────────────────────────────

function createOpenCashRegisterFail(): CashRegister
{
    return CashRegister::factory()->create([
        'status'    => CashRegisterStatus::OPEN,
        'opened_at' => now('America/Costa_Rica')->setTimezone('UTC'),
    ]);
}

function createAdminForPurchasesFail(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create();
}

function buildUpsertPurchaseActionFail(): UpsertPurchaseAction
{
    return new UpsertPurchaseAction(
        new ProcessPaymentAction(new RegisterTransactionAction()),
        app(UpdatePaymentAction::class),
    );
}

function purchasePayloadFail(int $total = 10000): array
{
    $supplier = Supplier::factory()->create();

    return [
        'purchaseData' => [
            'supplier_id'    => $supplier->id,
            'invoice_number' => 'INV-FAIL-001',
            'date'           => now()->toDateTimeString(),
            'total'          => $total,
            'payment_status' => PaymentStatus::PAID->value,
        ],
        'detailsData' => [],
        'paymentData' => [[
            'amount'        => $total,
            'method'        => PaymentMethod::CASH->value,
            'change_amount' => 0,
            'date'          => now()->toDateTimeString(),
        ]],
    ];
}

// ═════════════════════════════════════════════════════════════════════════════
// PRUEBAS QUE DEBEN FALLAR
// ═════════════════════════════════════════════════════════════════════════════

/**
 * ❌ RAZÓN DE FALLO: Se aserta que crear una compra con pago NO genera
 * ninguna transacción en caja (count = 0), pero UpsertPurchaseAction →
 * ProcessPaymentAction → RegisterTransactionAction siempre crea una.
 */
test('CP-01_FAIL - purchase transaction count is incorrectly expected to be zero after creation', function () {
    $admin = createAdminForPurchasesFail();
    createOpenCashRegisterFail();
    $this->actingAs($admin);

    $payload = purchasePayloadFail(10000);
    buildUpsertPurchaseActionFail()->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    // ❌ Falla: se creó al menos una transacción, count() > 0
    expect(Transaction::count())->toBe(0);
});

/**
 * ❌ RAZÓN DE FALLO: Se aserta que la transacción generada por una compra
 * es de tipo INCOME (ingreso), pero RegisterTransactionAction mapea
 * Purchase::class → TransactionType::EXPENSE siempre.
 */
test('CP-02_FAIL - purchase transaction type is incorrectly expected to be income instead of expense', function () {
    $admin = createAdminForPurchasesFail();
    createOpenCashRegisterFail();
    $this->actingAs($admin);

    $payload  = purchasePayloadFail(15000);
    $purchase = buildUpsertPurchaseActionFail()->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    // ❌ Falla: el tipo real es EXPENSE, no INCOME.
    $payment = Payment::where('origin_type', Purchase::class)
        ->where('origin_id', $purchase->id)
        ->firstOrFail();

    expect($payment->transaction->type)->toBe(TransactionType::INCOME);
});

/**
 * ❌ RAZÓN DE FALLO: Se aserta que crear una compra sin caja abierta
 * NO lanza excepción y termina sin error, pero RegisterTransactionAction
 * lanza \Exception('No hay una caja abierta para registrar la transacción.')
 * cuando no encuentra un CashRegister con status OPEN del día.
 */
test('CP-03_FAIL - creating purchase without open cash register is incorrectly expected to succeed silently', function () {
    $admin = createAdminForPurchasesFail();
    $this->actingAs($admin);

    // Sin caja abierta
    CashRegister::query()->update(['status' => CashRegisterStatus::CLOSED]);

    $payload = purchasePayloadFail(10000);

    // ❌ Falla: la Action lanza excepción, no retorna silenciosamente
    $purchase = buildUpsertPurchaseActionFail()->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    expect($purchase)->not->toBeNull();
});

/**
 * ❌ RAZÓN DE FALLO: Se aserta que el monto de la transacción es el doble
 * del total de la compra (50 000 en vez de 25 000). Transaction::amount
 * guarda abs($payment->amount), que es exactamente el total pagado.
 */
test('CP-04_FAIL - expense transaction amount is incorrectly expected to be double the purchase total', function () {
    $admin = createAdminForPurchasesFail();
    createOpenCashRegisterFail();
    $this->actingAs($admin);

    $total    = 25000;
    $payload  = purchasePayloadFail($total);
    $purchase = buildUpsertPurchaseActionFail()->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    // ❌ Falla: el monto real es 25 000, no 50 000.
    // Se obtiene el pago directamente desde BD para evitar null en morphOne.
    $payment = Payment::where('origin_type', Purchase::class)
        ->where('origin_id', $purchase->id)
        ->firstOrFail();

    expect($payment->transaction->amount)->toBe($total * 2);
});

/**
 * ❌ RAZÓN DE FALLO: Se aserta que la transacción NO tiene asignado
 * un cash_register_id, pero RegisterTransactionAction siempre asocia
 * la transacción a la caja abierta del día antes de persistirla.
 */
test('CP-05_FAIL - purchase transaction is incorrectly expected to have no cash register assigned', function () {
    $admin        = createAdminForPurchasesFail();
    $cashRegister = createOpenCashRegisterFail();
    $this->actingAs($admin);

    $payload  = purchasePayloadFail(10000);
    $purchase = buildUpsertPurchaseActionFail()->execute(
        $payload['purchaseData'],
        $payload['detailsData'],
        $payload['paymentData'],
    );

    // ❌ Falla: la transacción sí tiene cash_register_id asignado.
    $payment = Payment::where('origin_type', Purchase::class)
        ->where('origin_id', $purchase->id)
        ->firstOrFail();

    expect($payment->transaction->cash_register_id)->toBeNull();
});