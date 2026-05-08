<?php

use App\Enums\CashRegisterStatus;
use App\Enums\MealTime;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\CashRegister;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->withRole(UserRole::ADMIN)->create();
    $this->client = Client::factory()->create();
    $this->dish = Product::factory()->create(['sale_price' => 2000]);

    // Abrir caja para el día de hoy (necesario para transacciones)
    CashRegister::factory()->create([
        'status' => CashRegisterStatus::OPEN,
        'opened_at' => now(),
    ]);
});

test('can create contract with details and payment', function () {
    $payload = [
        'client_id' => $this->client->id,
        'business_name' => 'Empresa Test S.A.',
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->addDays(5)->format('Y-m-d'),
        'days_to_serve' => ['Monday', 'Tuesday'],
        'portions_per_day' => 10,
        'total_value' => 40000,
        'payment_status' => PaymentStatus::PAID->value,
        'contract_details' => [
            [
                'product_id' => $this->dish->id,
                'meal_time' => MealTime::LUNCH->value,
                'serve_date' => now()->format('Y-m-d'),
            ]
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 40000,
                'change_amount' => 0,
            ]
        ]
    ];

    $response = $this->actingAs($this->admin)
        ->postJson(route('contracts.store'), $payload);

    $response->assertStatus(201);
    $this->assertDatabaseHas('contracts', ['business_name' => 'Empresa Test S.A.']);
    $this->assertDatabaseHas('contract_details', ['product_id' => $this->dish->id]);
    $this->assertDatabaseHas('transactions', ['amount' => 40000]);
});

test('fails if payment is insufficient', function () {
    $payload = [
        'client_id' => $this->client->id,
        'business_name' => 'Empresa Deudora',
        'total_value' => 5000,
        'payment_status' => PaymentStatus::PAID->value,
        'contract_details' => [
            ['product_id' => $this->dish->id, 'meal_time' => MealTime::LUNCH->value, 'serve_date' => now()->format('Y-m-d')]
        ],
        'payment_details' => [
            ['method' => PaymentMethod::CASH->value, 'amount' => 2000, 'change_amount' => 0]
        ]
    ];

    $this->actingAs($this->admin)
        ->postJson(route('contracts.store'), $payload)
        ->assertJsonValidationErrors(['payment_details']);
});

test('resolves uniqueness conflict with soft deleted records', function () {
    // 1. Crear un contrato con datos controlados
    $contract = Contract::factory()->create([
        'client_id' => $this->client->id,
        'start_date' => now()->startOfDay()->format('Y-m-d'),
        'end_date' => now()->addDays(30)->format('Y-m-d'),
        'total_value' => 5000,
    ]);

    // Creamos un pago previo para que la validación de integridad pase (ya que el saldo será 0)
    Payment::factory()->create([
        'origin_id' => $contract->id,
        'origin_type' => Contract::class,
        'amount' => 5000,
        'change_amount' => 0,
        'method' => PaymentMethod::CASH->value,
        'date' => now(),
    ]);

    $detail = ContractDetail::create([
        'contract_id' => $contract->id,
        'product_id' => $this->dish->id,
        'meal_time' => MealTime::BREAKFAST->value,
        'serve_date' => now()->startOfDay()->format('Y-m-d'),
    ]);

    // 2. Eliminar el detalle (Soft Delete)
    $detail->delete();

    // 3. Intentar crear el MISMO detalle mediante el Upsert
    $payload = [
        'id' => $contract->id,
        'client_id' => $this->client->id,
        'business_name' => $contract->business_name,
        'start_date' => now()->startOfDay()->format('Y-m-d'),
        'end_date' => now()->addDays(30)->format('Y-m-d'),
        'days_to_serve' => $contract->days_to_serve,
        'portions_per_day' => $contract->portions_per_day,
        'total_value' => 5000,
        'payment_status' => PaymentStatus::PAID->value,
        'contract_details' => [
            [
                'product_id' => $this->dish->id,
                'meal_time' => MealTime::BREAKFAST->value,
                'serve_date' => now()->startOfDay()->format('Y-m-d'),
            ]
        ],
        'payment_details' => [] // Asumiendo que ya estaba pagado históricamente
    ];

    $this->actingAs($this->admin)
        ->putJson(route('contracts.update', $contract), $payload)
        ->assertStatus(200);

    // Verificar que no hay duplicados y que el original fue restaurado
    expect(ContractDetail::where('contract_id', $contract->id)->count())->toEqual(1);
    $this->assertDatabaseHas('contract_details', [
        'id' => $detail->id,
        'deleted_at' => null
    ]);
});

test('destroy contract soft deletes it', function () {
    $contract = Contract::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('contracts.destroy', $contract))
        ->assertRedirect();

    $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
});