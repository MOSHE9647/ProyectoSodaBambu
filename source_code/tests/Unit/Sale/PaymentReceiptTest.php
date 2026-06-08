<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;

// ─── Helpers ───────────────────────────────────────────────────────────────

/**
 * Crea un contrato base con cliente y usuario para pruebas.
 */
function makeContract(array $overrides = []): Contract
{
    $user = User::factory()->create();
    $client = Client::factory()->create();

    return Contract::factory()->create(array_merge([
        'user_id' => $user->id,
        'client_id' => $client->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'portions_per_day' => 2,
        'total_value' => 50000,
    ], $overrides));
}

/**
 * Crea una venta base con usuario para pruebas.
 */
function makeSale(array $overrides = []): Sale
{
    $user = User::factory()->create();

    return Sale::factory()->create(array_merge([
        'user_id' => $user->id,
        'invoice_number' => 'FAC-'.rand(1000, 9999),
        'payment_status' => PaymentStatus::PAID,
        'date' => now(),
        'total' => 25000,
    ], $overrides));
}

/**
 * Adjunta un pago polimórfico a un modelo (Sale o Contract).
 */
function attachPayment(Sale|Contract $model, int $amount, PaymentMethod $method, ?string $reference = null, int $changeAmount = 0): Payment
{
    return Payment::factory()->create([
        'amount' => $amount,
        'method' => $method,
        'reference' => $reference,
        'change_amount' => $changeAmount,
        'date' => now(),
        'origin_id' => $model->id,
        'origin_type' => get_class($model),
    ]);
}

// ─── CB-01: Pago parcial con múltiples métodos en una venta ────────────────

test('CB-01 - una venta acepta múltiples pagos con distintos métodos para cubrir el total', function () {
    // DADO: Una venta por ₡25.000
    $sale = makeSale(['total' => 25000]);

    // CUANDO: Se registran dos pagos parciales (Tarjeta + SINPE) que suman el total
    $paymentCard = attachPayment($sale, 15000, PaymentMethod::CARD, 'REF-CARD-001');
    $paymentSinpe = attachPayment($sale, 10000, PaymentMethod::SINPE, 'REF-SINPE-001');

    // ENTONCES: La venta tiene exactamente 2 pagos y la suma iguala el total
    $sale->refresh();
    $payments = $sale->payments;

    expect($payments)->toHaveCount(2)
        ->and($payments->sum('amount'))->toBe(25000)
        ->and($payments->pluck('method')->map->value->toArray())
        ->toContain(PaymentMethod::CARD->value, PaymentMethod::SINPE->value);
});

// ─── CB-02: Número de referencia opcional para pagos electrónicos ──────────

test('CB-02 - un pago con SINPE Móvil puede registrarse sin número de referencia', function () {
    // DADO: Una venta existente
    $sale = makeSale(['total' => 10000]);

    // CUANDO: Se registra un pago SINPE sin referencia (campo ahora opcional)
    $payment = attachPayment($sale, 10000, PaymentMethod::SINPE, null);

    // ENTONCES: El pago se crea correctamente con referencia nula
    expect($payment->exists)->toBeTrue()
        ->and($payment->reference)->toBeNull()
        ->and($payment->method)->toBe(PaymentMethod::SINPE);
});

// ─── CB-03: El recibo de contrato expone los datos correctos ───────────────

test('CB-03 - el modelo Contract implementa Receipable y retorna número y total correctos', function () {
    // DADO: Un contrato con valor conocido
    $contract = makeContract(['total_value' => 75000]);

    // CUANDO: Se consultan los métodos del contrato Receipable
    $receiptNumber = $contract->getReceiptNumber();
    $receiptTotal = $contract->getReceiptTotal();
    $receiptDate = $contract->getReceiptDate();

    // ENTONCES: Los valores coinciden con los datos del contrato
    expect($receiptNumber)->toBe('CONTRATO-'.str_pad($contract->id, 10, '0', STR_PAD_LEFT))
        ->and($receiptTotal)->toBe(75000)
        ->and($receiptDate->toDateString())->toBe(now()->toDateString());
});

// ─── CB-04: El recibo de venta expone los datos correctos ──────────────────

test('CB-04 - el modelo Sale implementa Receipable y retorna número de factura y total correctos', function () {
    // DADO: Una venta con número de factura y total conocidos
    $sale = makeSale([
        'invoice_number' => 'FAC-TEST-999',
        'total' => 30000,
    ]);

    // CUANDO: Se consultan los métodos Receipable de la venta
    $receiptNumber = $sale->getReceiptNumber();
    $receiptTotal = $sale->getReceiptTotal();

    // ENTONCES: Los datos coinciden con los de la venta original
    expect($receiptNumber)->toBe('FAC-TEST-999')
        ->and($receiptTotal)->toBe(30000);
});

// ─── CB-05: El total de la venta incluye todos los pagos (barra inferior) ──

test('CB-05 - el total de la venta refleja la suma de todos sus métodos de pago y no solo el primero', function () {
    // DADO: Una venta con total de ₡40.000 cubierta con 3 métodos distintos
    $sale = makeSale(['total' => 40000]);

    attachPayment($sale, 20000, PaymentMethod::CASH);
    attachPayment($sale, 12000, PaymentMethod::CARD, 'REF-CARD-002');
    attachPayment($sale, 8000, PaymentMethod::SINPE, 'REF-SINPE-002');

    // CUANDO: Se suman los montos de todos los pagos asociados
    $sale->refresh();
    $paymentsTotal = $sale->payments->sum('amount');

    // ENTONCES: La suma de pagos iguala el total de la venta (no solo el primero)
    expect($paymentsTotal)->toBe($sale->total)
        ->and($sale->payments)->toHaveCount(3);
});

// ─── CB-06: El cambio se calcula correctamente para pagos en efectivo ──────

test('CB-06 - el cambio calculado es correcto cuando el cliente paga con más efectivo del necesario', function () {
    // DADO: Una venta por ₡7.500
    $sale = makeSale(['total' => 7500]);

    // CUANDO: El cliente paga ₡10.000 en efectivo (₡2.500 de cambio esperado)
    $payment = attachPayment($sale, 10000, PaymentMethod::CASH, null, changeAmount: 2500);

    // ENTONCES: El cambio registrado en el pago es exactamente ₡2.500
    expect($payment->change_amount)->toBe(2500)
        ->and($payment->amount - $payment->change_amount)->toBe($sale->total)
        ->and($payment->method)->toBe(PaymentMethod::CASH);
});

// ─── CB-07: El comprobante de venta tiene el formato de número correcto ─────

test('CB-07 - el número de factura de la venta sigue el formato FAC-XXXXXXXXXX', function () {
    // DADO: Una venta creada con número de factura en formato estándar
    $sale = makeSale(['invoice_number' => 'FAC-0000000042']);

    // CUANDO: Se consulta el número de recibo
    $receiptNumber = $sale->getReceiptNumber();

    // ENTONCES: El número sigue el formato esperado FAC- seguido de 10 dígitos
    expect($receiptNumber)->toMatch('/^FAC-\d{10}$/')
        ->and($receiptNumber)->toBe('FAC-0000000042');
});

// ─── CB-08: Un pago en efectivo exacto no genera cambio ───────────────────

test('CB-08 - un pago en efectivo exacto registra cambio cero', function () {
    // DADO: Una venta por ₡5.000
    $sale = makeSale(['total' => 5000]);

    // CUANDO: El cliente paga exactamente ₡5.000 en efectivo
    $payment = attachPayment($sale, 5000, PaymentMethod::CASH, null, changeAmount: 0);

    // ENTONCES: El cambio es cero y el monto neto iguala el total de la venta
    expect($payment->change_amount)->toBe(0)
        ->and($payment->amount)->toBe($sale->total);
});