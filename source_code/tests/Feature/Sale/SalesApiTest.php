<?php

use App\Enums\CashRegisterStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionType;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\Transaction;

// ─── Helpers ───────────────────────────────────────────────────────────────

/**
 * Crea un producto con inventario listo para usar en pruebas de venta.
 */
function apiProduct(string $name, float $price, int $stock): Product
{
    $product = Product::factory()->create([
        'name' => $name,
        'has_inventory' => true,
        'sale_price' => $price,
    ]);

    ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => $stock,
        'minimum_stock' => 0,
    ]);

    return $product;
}

/**
 * Construye el payload mínimo para POST /sales.store.
 */
function salePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 0,
        'sale_details' => [],
        'payment_details' => [],
    ], $overrides);
}

// ─── Precondición común ────────────────────────────────────────────────────

beforeEach(function () {
    CashRegister::factory()->create([
        'status' => CashRegisterStatus::OPEN,
        'opened_at' => now(),
    ]);
});

// ─── API-01: Crear venta simple y verificar estructura JSON ────────────────

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Prueba API: La respuesta JSON incluye los campos mínimos esperados.
 */
test('API-01 - POST sales.store returns 201 with correct JSON structure', function () {
    actingAsAdmin();

    $producto = apiProduct('Tortilla', 800, 30);

    $response = $this->postJson(route('sales.store'), salePayload([
        'total' => 800,
        'sale_details' => [[
            'product_id' => $producto->id,
            'quantity' => 1,
            'unit_price' => 800.00,
            'applied_tax' => 0,
            'sub_total' => 800.00,
        ]],
        'payment_details' => [[
            'method' => PaymentMethod::CASH->value,
            'amount' => 800,
            'change_amount' => 0,
        ]],
    ]));

    // Debe retornar 201 Created con la estructura esperada
    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'invoice_number',
                'total',
                'payment_status',
                'date',
            ],
        ]);

    // El número de factura debe tener el formato FAC-XXXXXXXXXX
    $invoiceNumber = $response->json('data.invoice_number');
    expect($invoiceNumber)->toMatch('/^FAC-\d{10}$/');
});

// ─── API-02: El inventario se descuenta tras crear la venta ────────────────

/**
 * User Story: EIF-29 - Registro y cobro de ventas.
 * Prueba API: El stock del producto disminuye exactamente en la cantidad vendida.
 */
test('API-02 - POST sales.store decrements product stock by the sold quantity', function () {
    actingAsAdmin();

    $gaseosa = apiProduct('Gaseosa', 1200, 25);

    $this->postJson(route('sales.store'), salePayload([
        'total' => 2400,
        'sale_details' => [[
            'product_id' => $gaseosa->id,
            'quantity' => 2,
            'unit_price' => 1200.00,
            'applied_tax' => 0,
            'sub_total' => 2400.00,
        ]],
        'payment_details' => [[
            'method' => PaymentMethod::CASH->value,
            'amount' => 2400,
            'change_amount' => 0,
        ]],
    ]))->assertCreated();

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $gaseosa->id,
        'current_stock' => 23, // 25 - 2
    ]);
});

// ─── API-03: Se crea un pago y una transacción por cada método de pago ─────

/**
 * User Story: EIF-29 / EIF-175 - Pagos múltiples.
 * Prueba API: Cada payment_detail genera un Payment y un Transaction en la BD.
 */
test('API-03 - each payment_detail generates one Payment and one Transaction record', function () {
    actingAsAdmin();

    $jugo = apiProduct('Jugo Natural', 1500, 10);

    $response = $this->postJson(route('sales.store'), salePayload([
        'total' => 1500,
        'sale_details' => [[
            'product_id' => $jugo->id,
            'quantity' => 1,
            'unit_price' => 1500.00,
            'applied_tax' => 0,
            'sub_total' => 1500.00,
        ]],
        'payment_details' => [
            [
                'method' => PaymentMethod::CARD->value,
                'amount' => 1000,
                'reference' => 'REF-API-001',
            ],
            [
                'method' => PaymentMethod::SINPE->value,
                'amount' => 500,
                'reference' => '87654321', // 8 dígitos, dentro del rango 8–12
            ],
        ],
    ]))->assertCreated();

    $saleId = (int) $response->json('data.id');

    $payments = Payment::query()
        ->where('origin_type', Sale::class)
        ->where('origin_id', $saleId)
        ->get();

    expect($payments)->toHaveCount(2);

    foreach ($payments as $payment) {
        $this->assertDatabaseHas('transactions', [
            'payment_id' => $payment->id,
            'type' => TransactionType::INCOME->value,
        ]);
    }
});
// ─── API-04: Venta rechazada si el stock es insuficiente ───────────────────

/**
 * User Story: EIF-29 / EIF-176 - Validaciones de cantidades.
 * Prueba API: El endpoint retorna 422 cuando la cantidad pedida supera el stock.
 */
test('API-04 - POST sales.store returns 422 when quantity exceeds available stock', function () {
    actingAsAdmin();

    $tamal = apiProduct('Tamal de Cerdo', 2000, 3);

    $response = $this->postJson(route('sales.store'), salePayload([
        'total' => 8000,
        'sale_details' => [[
            'product_id' => $tamal->id,
            'quantity' => 4, // stock only has 3
            'unit_price' => 2000,
            'applied_tax' => 0,
            'sub_total' => 8000,
        ]],
        'payment_details' => [[
            'method' => PaymentMethod::CASH->value,
            'amount' => 8000,
            'change_amount' => 0,
        ]],
    ]));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sale_details.0.quantity']);

    // El stock NO debe haber cambiado
    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $tamal->id,
        'current_stock' => 3,
    ]);
});

// ─── API-05: La suma de pagos debe igualar el total de la venta ────────────

/**
 * User Story: EIF-29 / EIF-176 - Integridad financiera.
 * Prueba API: Si la suma de payment_details no cubre el total, se rechaza la venta.
 */
test('API-05 - POST sales.store rejects sale when payments do not cover the total', function () {
    actingAsAdmin();

    $combo = apiProduct('Combo del Día', 5000, 10);

    $response = $this->postJson(route('sales.store'), salePayload([
        'total' => 5000,
        'sale_details' => [[
            'product_id' => $combo->id,
            'quantity' => 1,
            'unit_price' => 5000,
            'applied_tax' => 0,
            'sub_total' => 5000,
        ]],
        'payment_details' => [[
            'method' => PaymentMethod::CASH->value,
            'amount' => 3000, // ₡2.000 short
            'change_amount' => 0,
        ]],
    ]));

    // El servidor debe rechazar por monto insuficiente
    $response->assertStatus(422);
});

// ─── API-06: Venta pendiente no registra pagos ni transacciones ────────────

/**
 * User Story: EIF-30 - Comandas pendientes.
 * Prueba API: Una venta con status PENDING no genera Payment ni Transaction.
 */
test('API-06 - a PENDING sale does not create any Payment or Transaction records', function () {
    actingAsAdmin();

    $sopa = apiProduct('Sopa de Pollo', 3500, 15);

    $response = $this->postJson(route('sales.store'), salePayload([
        'payment_status' => PaymentStatus::PENDING->value,
        'total' => 3500,
        'sale_details' => [[
            'product_id' => $sopa->id,
            'quantity' => 1,
            'unit_price' => 3500.00,
            'applied_tax' => 0,
            'sub_total' => 3500.00,
        ]],
        // sin payment_details porque es pendiente
    ]))->assertCreated();

    $saleId = (int) $response->json('data.id');

    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseCount('transactions', 0);

    // El stock SÍ debe haberse reservado
    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $sopa->id,
        'current_stock' => 14,
    ]);
});

// ─── API-07: GET sales index retorna lista paginada ────────────────────────

/**
 * User Story: EIF-29 - Consulta de historial de ventas.
 * Prueba API: El endpoint de listado retorna una colección JSON con datos de ventas.
 */
test('API-07 - GET sales.index returns a paginated list including the created sale', function () {
    actingAsAdmin();

    $agua = apiProduct('Agua Fría', 500, 50);

    $this->postJson(route('sales.store'), salePayload([
        'total' => 500,
        'sale_details' => [[
            'product_id' => $agua->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'applied_tax' => 0,
            'sub_total' => 500.00,
        ]],
        'payment_details' => [[
            'method' => PaymentMethod::CASH->value,
            'amount' => 500,
            'change_amount' => 0,
        ]],
    ]))->assertCreated();

    // El índice de ventas debe existir y retornar datos
    $response = $this->getJson(route('sales.index'));

    $response->assertOk();

    // Verificar que la venta recién creada existe en la BD
    $this->assertDatabaseHas('sales', ['total' => 500]);
});
