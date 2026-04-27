<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

function makeInventoryProduct(string $name, float $salePrice, int $stock): Product
{
    $product = Product::factory()->create([
        'name' => $name,
        'has_inventory' => true,
        'sale_price' => $salePrice,
    ]);

    ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => $stock,
        'minimum_stock' => 0,
    ]);

    return $product;
}

function baseSalePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 0,
        'sale_details' => [],
    ], $overrides);
}

function assertSaleMathIntegrity(Sale $sale): void
{
    $sale = $sale->fresh();

    $detailsTotal = (float) SaleDetail::query()
        ->where('sale_id', $sale->id)
        ->sum('sub_total');

    $paymentsTotal = (float) Payment::query()
        ->where('origin_type', Sale::class)
        ->where('origin_id', $sale->id)
        ->sum('amount');

    $transactionsTotal = (float) Transaction::query()
        ->whereIn('payment_id', function ($query) use ($sale) {
            $query->select('id')
                ->from('payments')
                ->where('origin_type', Sale::class)
                ->where('origin_id', $sale->id);
        })
        ->sum('amount');

    expect(round($detailsTotal, 2))->toBe(round((float) $sale->total, 2))
        ->and(round($paymentsTotal, 2))->toBe(round((float) $sale->total, 2))
        ->and(round($transactionsTotal, 2))->toBe(round((float) $sale->total, 2));
}

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Subtask: EIF-175 - Implementar modelo de pagos multiples por venta.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-175
 */
test('CP-01_EIF-175_EIF-29 - creates a simple paid cash sale and registers stock payment transaction and cache', function () {
    actingAsAdmin();

    $empanada = makeInventoryProduct('Empanada', 1500, 10);

    Cache::put('today_sales_stats', [
        'todaySalesTotal' => 0,
        'salesTrendText' => '0%',
        'trendDirection' => 'neutral',
    ], now()->addMinutes(10));

    $response = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 1500,
        'sale_details' => [
            [
                'product_id' => $empanada->id,
                'quantity' => 1,
                'unit_price' => 1500,
                'applied_tax' => 0,
                'sub_total' => 1500,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 1500,
                'change_amount' => 0,
            ],
        ],
    ]));

    $response->assertCreated();

    $saleId = (int) $response->json('data.id');
    $sale = Sale::findOrFail($saleId);

    expect($sale->invoice_number)->toMatch('/^FAC-\d{10}$/');

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $empanada->id,
        'current_stock' => 9,
    ]);

    $this->assertDatabaseCount('payments', 1);
    $payment = Payment::query()->where('origin_id', $sale->id)->firstOrFail();

    expect($payment->origin_type)->toBe(Sale::class)
        ->and((float) $payment->amount)->toBe(1500.0);

    $this->assertDatabaseHas('transactions', [
        'payment_id' => $payment->id,
        'type' => TransactionType::INCOME->value,
        'amount' => 1500,
        'concept' => "Pago de venta #{$sale->invoice_number}",
    ]);

    $stats = Cache::get('today_sales_stats');
    expect(is_array($stats))->toBeTrue()
        ->and((float) ($stats['todaySalesTotal'] ?? 0))->toBe(1500.0);

    assertSaleMathIntegrity($sale);
});

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Subtask: EIF-176 - Validaciones de montos y metodos.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-176
 */
test('CP-02_EIF-176_EIF-29 - accepts split payments and creates one transaction per payment for cash closing traceability', function () {
    actingAsAdmin();

    $casado = makeInventoryProduct('Casado Grande', 10000, 15);

    $response = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 10000,
        'sale_details' => [
            [
                'product_id' => $casado->id,
                'quantity' => 1,
                'unit_price' => 10000,
                'applied_tax' => 0,
                'sub_total' => 10000,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 2000,
                'change_amount' => 0,
            ],
            [
                'method' => PaymentMethod::SINPE->value,
                'amount' => 8000,
                'reference' => '12345678',
            ],
        ],
    ]));

    $response->assertCreated();

    $saleId = (int) $response->json('data.id');
    $sale = Sale::findOrFail($saleId);

    $payments = Payment::query()
        ->where('origin_type', Sale::class)
        ->where('origin_id', $sale->id)
        ->orderBy('id')
        ->get();

    expect($payments)->toHaveCount(2)
        ->and((float) $payments[0]->amount)->toBe(2000.0)
        ->and($payments[0]->method)->toBe(PaymentMethod::CASH)
        ->and((float) $payments[1]->amount)->toBe(8000.0)
        ->and($payments[1]->method)->toBe(PaymentMethod::SINPE);

    $transactions = Transaction::query()
        ->whereIn('payment_id', $payments->pluck('id'))
        ->orderBy('id')
        ->get();

    expect($transactions)->toHaveCount(2)
        ->and((float) $transactions[0]->amount)->toBe(2000.0)
        ->and((float) $transactions[1]->amount)->toBe(8000.0);

    assertSaleMathIntegrity($sale);
});

/**
 * User Story: EIF-30 - Crear y actualizar comandas pendientes.
 * Subtask: EIF-183 - Registro de venta pendiente desde Revisar Orden.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-183
 */
test('CP-03_EIF-183_EIF-30 - updates pending order quantity and adjusts stock only by the quantity delta without creating financial records', function () {
    actingAsAdmin();

    $refresco = makeInventoryProduct('Refresco', 1200, 20);

    $createResponse = $this->postJson(route('sales.store'), baseSalePayload([
        'payment_status' => PaymentStatus::PENDING->value,
        'total' => 3600,
        'sale_details' => [
            [
                'product_id' => $refresco->id,
                'quantity' => 3,
                'unit_price' => 1200,
                'applied_tax' => 0,
                'sub_total' => 3600,
            ],
        ],
    ]));

    $createResponse->assertCreated();

    $saleId = (int) $createResponse->json('data.id');
    $sale = Sale::findOrFail($saleId);
    $detail = SaleDetail::query()->where('sale_id', $sale->id)->firstOrFail();

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $refresco->id,
        'current_stock' => 17,
    ]);

    $updateResponse = $this->postJson(route('sales.store'), baseSalePayload([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PENDING->value,
        'total' => 6000,
        'sale_details' => [
            [
                'id' => $detail->id,
                'product_id' => $refresco->id,
                'quantity' => 5,
                'unit_price' => 1200,
                'applied_tax' => 0,
                'sub_total' => 6000,
            ],
        ],
    ]));

    $updateResponse->assertCreated();

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $refresco->id,
        'current_stock' => 15,
    ]);

    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('transactions', 0);
});

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Subtask: EIF-178 - Finalizacion de venta condicionada a restante cero.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-178
 */
test('CP-04_EIF-178_EIF-29 - changing product in a paid order restores old stock decreases new stock and keeps financial totals intact', function () {
    actingAsAdmin();

    $casadoPollo = makeInventoryProduct('Casado de Pollo', 5500, 10);
    $casadoCarne = makeInventoryProduct('Casado de Carne', 5500, 8);

    $createResponse = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 5500,
        'sale_details' => [
            [
                'product_id' => $casadoPollo->id,
                'quantity' => 1,
                'unit_price' => 5500,
                'applied_tax' => 0,
                'sub_total' => 5500,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 5500,
                'change_amount' => 0,
            ],
        ],
    ]));

    $createResponse->assertCreated();

    $saleId = (int) $createResponse->json('data.id');
    $sale = Sale::findOrFail($saleId);

    $detail = SaleDetail::query()->where('sale_id', $sale->id)->firstOrFail();
    $originalPayment = Payment::query()->where('origin_id', $sale->id)->firstOrFail();
    $originalTransaction = Transaction::query()->where('payment_id', $originalPayment->id)->firstOrFail();

    $updateResponse = $this->postJson(route('sales.store'), baseSalePayload([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PAID->value,
        'total' => 5500,
        'sale_details' => [
            [
                'id' => $detail->id,
                'product_id' => $casadoCarne->id,
                'quantity' => 1,
                'unit_price' => 5500,
                'applied_tax' => 0,
                'sub_total' => 5500,
            ],
        ],
        'payment_details' => [
            [
                'id' => $originalPayment->id,
                'method' => PaymentMethod::CASH->value,
                'amount' => 5500,
                'change_amount' => 0,
            ],
        ],
    ]));

    $updateResponse->assertCreated();

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $casadoPollo->id,
        'current_stock' => 10,
    ]);

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $casadoCarne->id,
        'current_stock' => 7,
    ]);

    $sale->refresh();
    $payment = Payment::query()->where('origin_id', $sale->id)->firstOrFail();
    $transaction = Transaction::query()->where('payment_id', $payment->id)->firstOrFail();

    expect($payment->id)->toBe($originalPayment->id)
        ->and($transaction->id)->toBe($originalTransaction->id)
        ->and((float) $payment->amount)->toBe(5500.0)
        ->and((float) $transaction->amount)->toBe(5500.0);

    assertSaleMathIntegrity($sale);
});

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Subtasks: EIF-176, EIF-177, EIF-179 - validaciones, total pagado/restante y trazabilidad.
 * Priority: Highest
 * Jira Links:
 * - https://est-una.atlassian.net/browse/EIF-176
 * - https://est-una.atlassian.net/browse/EIF-177
 * - https://est-una.atlassian.net/browse/EIF-179
 */
test('CP-05_EIF-176_EIF-177_EIF-179_EIF-29 - updates existing payment method and enforces reference validation for card payments', function () {
    actingAsAdmin();

    $casado = makeInventoryProduct('Casado Ejecutivo', 5000, 12);

    $createResponse = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 5000,
        'sale_details' => [
            [
                'product_id' => $casado->id,
                'quantity' => 1,
                'unit_price' => 5000,
                'applied_tax' => 0,
                'sub_total' => 5000,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 5000,
                'change_amount' => 0,
            ],
        ],
    ]));

    $createResponse->assertCreated();

    $saleId = (int) $createResponse->json('data.id');
    $sale = Sale::findOrFail($saleId);
    $detail = SaleDetail::query()->where('sale_id', $sale->id)->firstOrFail();
    $existingPayment = Payment::query()->where('origin_id', $sale->id)->firstOrFail();

    $updateResponse = $this->postJson(route('sales.store'), baseSalePayload([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PAID->value,
        'total' => 5000,
        'sale_details' => [
            [
                'id' => $detail->id,
                'product_id' => $casado->id,
                'quantity' => 1,
                'unit_price' => 5000,
                'applied_tax' => 0,
                'sub_total' => 5000,
            ],
        ],
        'payment_details' => [
            [
                'id' => $existingPayment->id,
                'method' => PaymentMethod::CARD->value,
                'amount' => 5000,
                'reference' => 'CARD-778899',
            ],
        ],
    ]));

    $updateResponse->assertCreated();

    $updatedPayment = $existingPayment->fresh();
    expect($updatedPayment->id)->toBe($existingPayment->id)
        ->and($updatedPayment->method)->toBe(PaymentMethod::CARD)
        ->and($updatedPayment->reference)->toBe('CARD-778899');

    $this->assertDatabaseCount('payments', 1);

    $transaction = Transaction::query()->where('payment_id', $existingPayment->id)->firstOrFail();
    expect($transaction)->not->toBeNull();

    assertSaleMathIntegrity($sale);

    $invalidResponse = $this->postJson(route('sales.store'), baseSalePayload([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PAID->value,
        'total' => 5000,
        'sale_details' => [
            [
                'id' => $detail->id,
                'product_id' => $casado->id,
                'quantity' => 1,
                'unit_price' => 5000,
                'applied_tax' => 0,
                'sub_total' => 5000,
            ],
        ],
        'payment_details' => [
            [
                'id' => $existingPayment->id,
                'method' => PaymentMethod::CARD->value,
                'amount' => 5000,
            ],
        ],
    ]));

    $invalidResponse
        ->assertStatus(422)
        ->assertJsonValidationErrors(['payment_details.0.reference']);
});

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Subtasks: EIF-175, EIF-177, EIF-179.
 * Priority: High
 */
test('CP-06_EIF-175_EIF-177_EIF-179_EIF-29 - keeps invoice sequence format and one transaction per payment', function () {
    actingAsAdmin();

    $productoA = makeInventoryProduct('Cafe Negro', 1000, 20);
    $productoB = makeInventoryProduct('Te Frio', 1000, 20);

    $first = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 1000,
        'sale_details' => [
            [
                'product_id' => $productoA->id,
                'quantity' => 1,
                'unit_price' => 1000,
                'applied_tax' => 0,
                'sub_total' => 1000,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::SINPE->value,
                'amount' => 1000,
                'reference' => 'SINPE-1000',
            ],
        ],
    ]));

    $first->assertCreated();

    $second = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 1000,
        'sale_details' => [
            [
                'product_id' => $productoB->id,
                'quantity' => 1,
                'unit_price' => 1000,
                'applied_tax' => 0,
                'sub_total' => 1000,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 1000,
                'change_amount' => 0,
            ],
        ],
    ]));

    $second->assertCreated();

    $firstSale = Sale::findOrFail((int) $first->json('data.id'));
    $secondSale = Sale::findOrFail((int) $second->json('data.id'));

    expect($firstSale->invoice_number)->toMatch('/^FAC-\d{10}$/')
        ->and($secondSale->invoice_number)->toMatch('/^FAC-\d{10}$/');

    $firstNumeric = (int) substr($firstSale->invoice_number, 4);
    $secondNumeric = (int) substr($secondSale->invoice_number, 4);
    expect($secondNumeric)->toBe($firstNumeric + 1);

    $payments = Payment::query()
        ->where('origin_type', Sale::class)
        ->whereIn('origin_id', [$firstSale->id, $secondSale->id])
        ->get();

    foreach ($payments as $payment) {
        $this->assertDatabaseHas('transactions', ['payment_id' => $payment->id]);
    }
});

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Subtask: EIF-176 - Validaciones de montos y metodos.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-176
 */
test('CP-07_EIF-176_EIF-29 - rejects sale when requested quantity exceeds available stock', function () {
    actingAsAdmin();

    $producto = makeInventoryProduct('Tamal', 1500, 5);

    $response = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 9000,
        'sale_details' => [
            [
                'product_id' => $producto->id,
                'quantity' => 6,
                'unit_price' => 1500,
                'applied_tax' => 0,
                'sub_total' => 9000,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 9000,
                'change_amount' => 0,
            ],
        ],
    ]));

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sale_details.0.quantity']);
});

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Subtasks: EIF-178, EIF-179 - integridad al anular/retroceder cambios.
 * Priority: Highest
 * Jira Links:
 * - https://est-una.atlassian.net/browse/EIF-178
 * - https://est-una.atlassian.net/browse/EIF-179
 */
test('CP-08_EIF-178_EIF-179_EIF-29 - deleting a paid sale restores stock and soft deletes related payments and transactions', function () {
    actingAsAdmin();

    $producto = makeInventoryProduct('Arroz con pollo', 3500, 10);

    $createResponse = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 3500,
        'sale_details' => [
            [
                'product_id' => $producto->id,
                'quantity' => 1,
                'unit_price' => 3500,
                'applied_tax' => 0,
                'sub_total' => 3500,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CARD->value,
                'amount' => 3500,
                'reference' => '000123456789',
            ],
        ],
    ]));

    $createResponse->assertCreated();

    $sale = Sale::findOrFail((int) $createResponse->json('data.id'));
    $payment = Payment::query()->where('origin_type', Sale::class)->where('origin_id', $sale->id)->firstOrFail();
    $transaction = Transaction::query()->where('payment_id', $payment->id)->firstOrFail();

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $producto->id,
        'current_stock' => 9,
    ]);

    $sale->delete();

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $producto->id,
        'current_stock' => 10,
    ]);

    $this->assertSoftDeleted('payments', ['id' => $payment->id]);
    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
});

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Subtask: EIF-176 - Validaciones de montos y metodos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-176
 */
test('CP-09_EIF-176_EIF-29 - rejects duplicated products in sale details', function () {
    actingAsAdmin();

    $producto = makeInventoryProduct('Pinto', 2500, 20);

    $response = $this->postJson(route('sales.store'), baseSalePayload([
        'total' => 5000,
        'sale_details' => [
            [
                'product_id' => $producto->id,
                'quantity' => 1,
                'unit_price' => 2500,
                'applied_tax' => 0,
                'sub_total' => 2500,
            ],
            [
                'product_id' => $producto->id,
                'quantity' => 1,
                'unit_price' => 2500,
                'applied_tax' => 0,
                'sub_total' => 2500,
            ],
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 5000,
                'change_amount' => 0,
            ],
        ],
    ]));

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['sale_details']);
});
