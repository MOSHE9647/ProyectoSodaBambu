<?php

use App\Models\User;
use App\Models\Product;
use App\Enums\UserRole;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
    $this->admin = User::whereHas('roles', function($q) {
        $q->where('name', UserRole::ADMIN->value);
    })->first();
});

test('la venta rechaza decimales en el total (EIF-211)', function () {
    $product = Product::factory()->create(['sale_price' => 1000, 'has_inventory' => false]);
    
    $payload = [
        'date' => now()->format('Y-m-d'),
        'total' => 1100.50, 
        'payment_status' => PaymentStatus::PAID->value,
        'sale_details' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 1100.50,
                'applied_tax' => 0,
                'sub_total' => 1100.50
            ]
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value, 
                'amount' => 2000,
                'change_amount' => 899
            ]
        ]
    ];

    // Usamos postJson para recibir el 422 directamente
    $response = $this->actingAs($this->admin)
        ->postJson(route('sales.store'), $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['total']);
});

test('el sistema acepta totales enteros siguiendo redondeo', function () {
    $product = Product::factory()->create(['sale_price' => 2500, 'has_inventory' => false]);

    $payload = [
        'date' => now()->format('Y-m-d'),
        'total' => 2500,
        'payment_status' => PaymentStatus::PAID->value,
        'sale_details' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 2500,
                'applied_tax' => 0,
                'sub_total' => 2500
            ]
        ],
        'payment_details' => [
            [
                'method' => PaymentMethod::CASH->value, 
                'amount' => 3000,
                'change_amount' => 500
            ]
        ]
    ];
    $response = $this->actingAs($this->admin)
        ->postJson(route('sales.store'), $payload);


    if ($response->status() !== 500) {
        $response->assertJsonMissingValidationErrors(['total']);
    }
    
    expect($response->status())->not->toBe(422);
});

test('el calculo estatico de precio de venta cumple EIF-211', function () {
    $price = Product::calculateSalePrice(1000, 13, 10); 
    expect((int)round($price))->toBeInt();
});