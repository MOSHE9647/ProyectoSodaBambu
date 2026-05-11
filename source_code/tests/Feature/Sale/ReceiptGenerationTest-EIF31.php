<?php

use App\Models\User;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
    $this->admin = User::whereHas('roles', function($q) {
        $q->where('name', UserRole::ADMIN->value);
    })->first();

    // Simulación de apertura de caja necesaria para transacciones financieras
    CashRegister::factory()->create([
        'user_id' => $this->admin->id,
        'status' => 'open',
        'opening_balance' => 50000,
        'opened_at' => now(),
    ]);
});

test('la respuesta de la venta contiene todos los datos necesarios para el tiquete (HU EIF-31)', function () {
    // 1. Preparar producto con nombre específico para validar que llegue al tiquete
    $product = Product::factory()->create([
        'name' => 'Refresco de Bambú',
        'sale_price' => 1500
    ]);
    ProductStock::factory()->create(['product_id' => $product->id, 'current_stock' => 10]);

    // 2. Payload completo de la venta
    $payload = [
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->toDateString(),
        'total' => 1500,
        'sale_details' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 1500,
                'applied_tax' => 0,
                'sub_total' => 1500
            ]
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 1500,
                'change_amount' => 0
            ]
        ]
    ];

    // 3. Ejecutar la petición
    $response = $this->actingAs($this->admin)
        ->postJson(route('sales.store'), $payload);

    // 4. Validar estructura JSON requerida por el frontend para imprimir
    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'invoice_number',
                'total',
                'sale_details' => [
                    '*' => [
                        'product' => ['name']
                    ]
                ],
                'payments' => [
                    '*' => ['method', 'amount']
                ]
            ]
        ]);

    // 5. Validar integridad de los datos
    $responseData = $response->json('data');
    expect($responseData['sale_details'][0]['product']['name'])->toBe('Refresco de Bambú');
    expect($responseData['payments'][0]['method'])->toBe(PaymentMethod::CASH->value);
});

test('el modelo Sale implementa correctamente la lógica de Receipable', function () {
    // Para que canGenerateReceipt sea true, necesita invoice_number y al menos un detalle
    $sale = Sale::factory()->create([
        'total' => 5000,
        'invoice_number' => 'FAC-TEST-001'
    ]);
    
    SaleDetail::factory()->create([
        'sale_id' => $sale->id,
        'quantity' => 1,
        'unit_price' => 5000,
        'sub_total' => 5000
    ]);
    
    $sale->refresh();

    expect($sale)->toBeInstanceOf(\App\Contracts\Receipable::class);
    expect($sale->getReceiptTotal())->toBe(5000);
    expect($sale->canGenerateReceipt())->toBeTrue();
});