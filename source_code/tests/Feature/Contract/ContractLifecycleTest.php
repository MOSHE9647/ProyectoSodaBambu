<?php

use App\Models\User;
use App\Models\Client;
use App\Models\Product;
use App\Models\Contract;
use App\Models\CashRegister;
use App\Enums\UserRole;
use App\Enums\MealTime;
use App\Enums\WeekDay;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
    $this->admin = User::whereHas('roles', function($q) {
        $q->where('name', UserRole::ADMIN->value);
    })->first();

    // Abrir caja para permitir pagos
    CashRegister::factory()->create([
        'user_id' => $this->admin->id,
        'status' => 'open',
    ]);
});

test('un administrador puede crear un contrato con menú aleatorio y procesar el pago (EIF-43, 216, 46)', function () {
    $client = Client::factory()->create();
    $product = Product::factory()->create(['sale_price' => 2000]);
    
    $startDate = now()->addDay()->toDateString();
    $endDate = now()->addDays(3)->toDateString();

    // PAYLOAD CORREGIDO según los errores de validación del backend
    $payload = [
        'client_id' => $client->id,
        'business_name' => 'Constructora El Bambú', 
        'start_date' => $startDate,
        'end_date' => $endDate,
        'days_to_serve' => [WeekDay::MONDAY->value], 
        'portions_per_day' => 10, 
        'total_value' => 20000, 
        'payment_status' => PaymentStatus::PAID->value, 
        
        'contract_details' => [
            [
                'product_id' => $product->id,
                'meal_time' => MealTime::LUNCH->value,
                'serve_date' => $startDate, 
                'portions' => 10,
                'unit_price' => 2000,
                'sub_total' => 20000
            ]
        ],
        
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value,
                'amount' => 20000,
                'change_amount' => 0
            ]
        ]
    ];

    $response = $this->actingAs($this->admin)
        ->postJson(route('contracts.store'), $payload);

    // Si hay errores, los imprimimos para depurar rápido
    if ($response->status() !== 201) {
        dump($response->json());
    }

    $response->assertStatus(201);
    
    $this->assertDatabaseHas('contracts', [
        'client_id' => $client->id,
        'business_name' => 'Constructora El Bambú',
        'portions_per_day' => 10
    ]);
});

test('validación de fechas en contratos', function () {
    $client = Client::factory()->create();

    $payload = [
        'client_id' => $client->id,
        'business_name' => 'Test Error',
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->toDateString(),
        'days_to_serve' => [WeekDay::MONDAY->value],
        'portions_per_day' => 5,
        'total_value' => 5000,
        'payment_status' => PaymentStatus::PENDING->value,
        'contract_details' => []
    ];

    $response = $this->actingAs($this->admin)
        ->postJson(route('contracts.store'), $payload);

    $response->assertStatus(422);
});