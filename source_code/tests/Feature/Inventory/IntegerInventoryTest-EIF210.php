<?php

use App\Models\User;
use App\Models\Category;
use App\Enums\UserRole;
use App\Enums\ProductType; 
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
    $this->admin = User::whereHas('roles', function($q) {
        $q->where('name', UserRole::ADMIN->value);
    })->first();
});

test('el modulo de productos rechaza precios decimales y almacena enteros (EIF-210)', function () {
    $category = Category::factory()->create();
    $validType = ProductType::cases()[0]->value;

    // 1. Validar que rechaza decimales
    $payloadConDecimales = [
        'name' => 'Producto Decimal Test',
        'category_id' => $category->id,
        'sale_price' => 1500.50,
        'reference_cost' => 1000.75,
        'description' => 'Test',
        'type' => $validType,
        'has_inventory' => 1,
        'minimum_stock' => 1,
        'expiration_date' => now()->addYear()->format('Y-m-d'),
        'expiration_alert_days' => 30,
        'tax_percentage' => 13
    ];

    $response = $this->actingAs($this->admin)
        ->postJson(route('products.store'), $payloadConDecimales);

    $response->assertStatus(422);

    // 2. Crear un producto con enteros
    $payloadCorrecto = [
        'name' => 'Producto Entero Final',
        'category_id' => $category->id,
        'sale_price' => 2500,
        'reference_cost' => 1800,
        'description' => 'Test de integración exitoso',
        'type' => $validType,
        'has_inventory' => 1,
        'minimum_stock' => 1,
        'expiration_date' => now()->addYear()->format('Y-m-d'),
        'expiration_alert_days' => 30,
        'tax_percentage' => 13
    ];

    $response = $this->actingAs($this->admin)
        ->postJson(route('products.store'), $payloadCorrecto);

    expect($response->status())->toBeIn([200, 201, 302]);

    // VALIDACIÓN FLEXIBLE: Verificamos que se guardó y que el precio NO tiene decimales
    $this->assertDatabaseHas('products', [
        'name' => 'Producto Entero Final',
        'reference_cost' => 1800
    ]);

    $product = \App\Models\Product::where('name', 'Producto Entero Final')->first();
    // Verificamos que sea un número entero (sin puntos decimales significativos)
    expect($product->sale_price)->toBeInt();
});

test('el modulo de insumos maneja costos como enteros (EIF-210)', function () {
    $routeName = 'supplies.store'; 

    $payload = [
        'name' => 'Insumo Test Final',
        'unit_price' => 500,
        'measure_unit' => 'kg',
        'quantity' => 10,
        'description' => 'Test'
    ];

    $response = $this->actingAs($this->admin)
        ->postJson(route($routeName), $payload);
    
    expect($response->status())->toBeIn([200, 201, 302]);

    $this->assertDatabaseHas('supplies', [
        'name' => 'Insumo Test Final',
        'unit_price' => 500 
    ]);
});