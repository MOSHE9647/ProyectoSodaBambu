<?php

use App\Actions\Sale\UpsertSaleAction;
use App\Enums\PaymentStatus;
use App\Models\Product;

/**
 * User Story: EIF-30 - Create and update pending orders.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-30
 */
test('CP-01_EIF-30 - creates sale with deterministic invoice number based on sale id', function () {
    actingAsAdmin();

    $product = Product::factory()->create();
    $action = app(UpsertSaleAction::class);

    $sale = $action->execute([
        'payment_status' => PaymentStatus::PENDING->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 120.00,
    ], [
        [
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 60.00,
            'applied_tax' => 0.00,
            'sub_total' => 120.00,
        ],
    ], null);

    $sale->refresh();

    expect($sale->invoice_number)->toBe('FAC-'.str_pad((string) $sale->id, 10, '0', STR_PAD_LEFT));
});

/**
 * User Story: EIF-30 - Create and update pending orders.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-183
 */
test('CP-02_EIF-183 - creates pending sale with two details', function () {
    actingAsAdmin();

    $gallopinto = Product::factory()->create();
    $coffee = Product::factory()->create();
    $action = app(UpsertSaleAction::class);

    $sale = $action->execute([
        'payment_status' => PaymentStatus::PENDING->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 250.00,
    ], [
        [
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
        [
            'product_id' => $coffee->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'applied_tax' => 0.00,
            'sub_total' => 50.00,
        ],
    ], null);

    $sale = $sale->fresh();

    expect($sale->saleDetails()->count('*'))->toBe(2);
});

/**
 * User Story: EIF-30 - Create and update pending orders.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-183
 */
test('CP-03_EIF-183 - updates pending sale with hard delete for removed details', function () {
    actingAsAdmin();

    $gallopinto = Product::factory()->create();
    $coffee = Product::factory()->create();
    $empanada = Product::factory()->create();
    $action = app(UpsertSaleAction::class);

    $sale = $action->execute([
        'payment_status' => PaymentStatus::PENDING->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 250.00,
    ], [
        [
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
        [
            'product_id' => $coffee->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'applied_tax' => 0.00,
            'sub_total' => 50.00,
        ],
    ], null);

    $sale = $sale->fresh();

    $gallopintoDetail = $sale->saleDetails()
        ->where('product_id', '=', $gallopinto->id)
        ->firstOrFail(['*']);
    $coffeeDetail = $sale->saleDetails()
        ->where('product_id', '=', $coffee->id)
        ->firstOrFail(['*']);

    $action->execute([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PENDING->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 280.00,
    ], [
        [
            'id' => $gallopintoDetail->id,
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
        [
            'product_id' => $empanada->id,
            'quantity' => 2,
            'unit_price' => 40.00,
            'applied_tax' => 0.00,
            'sub_total' => 80.00,
        ],
    ], null);

    $sale = $sale->fresh();

    expect($sale->saleDetails()->count('*'))->toBe(2);
    $this->assertDatabaseMissing('sale_details', ['id' => $coffeeDetail->id]);
});

/**
 * User Story: EIF-30 - Create and update pending orders.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-183
 */
test('CP-04_EIF-183 - changes sale status from pending to paid', function () {
    actingAsAdmin();

    $gallopinto = Product::factory()->create();
    $coffee = Product::factory()->create();
    $action = app(UpsertSaleAction::class);

    $sale = $action->execute([
        'payment_status' => PaymentStatus::PENDING->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 250.00,
    ], [
        [
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
        [
            'product_id' => $coffee->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'applied_tax' => 0.00,
            'sub_total' => 50.00,
        ],
    ], null);

    $sale = $sale->fresh();

    $gallopintoDetail = $sale->saleDetails()
        ->where('product_id', '=', $gallopinto->id)
        ->firstOrFail(['*']);
    $coffeeDetail = $sale->saleDetails()
        ->where('product_id', '=', $coffee->id)
        ->firstOrFail(['*']);

    $action->execute([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 250.00,
    ], [
        [
            'id' => $gallopintoDetail->id,
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
        [
            'id' => $coffeeDetail->id,
            'product_id' => $coffee->id,
            'quantity' => 1,
            'unit_price' => 50.00,
            'applied_tax' => 0.00,
            'sub_total' => 50.00,
        ],
    ], null);

    expect($sale->fresh()->payment_status)->toBe(PaymentStatus::PAID);
    expect($sale->fresh()->saleDetails()->count('*'))->toBe(2);
});

/**
 * User Story: EIF-30 - Create and update pending orders.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-183
 */
test('CP-05_EIF-183 - updates paid sale with soft delete for removed details', function () {
    actingAsAdmin();

    $gallopinto = Product::factory()->create();
    $empanada = Product::factory()->create();
    $action = app(UpsertSaleAction::class);

    $sale = $action->execute([
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 280.00,
    ], [
        [
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
        [
            'product_id' => $empanada->id,
            'quantity' => 2,
            'unit_price' => 40.00,
            'applied_tax' => 0.00,
            'sub_total' => 80.00,
        ],
    ], null);

    $sale = $sale->fresh();

    $gallopintoDetail = $sale->saleDetails()
        ->where('product_id', '=', $gallopinto->id)
        ->firstOrFail(['*']);
    $empanadaDetail = $sale->saleDetails()
        ->where('product_id', '=', $empanada->id)
        ->firstOrFail(['*']);

    $action->execute([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 200.00,
    ], [
        [
            'id' => $gallopintoDetail->id,
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
    ], null);

    $this->assertSoftDeleted('sale_details', ['id' => $empanadaDetail->id]);
});

/**
 * User Story: EIF-30 - Create and update pending orders.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-183
 */
test('CP-06_EIF-183 - restores soft deleted detail when sent again in paid sale', function () {
    actingAsAdmin();

    $gallopinto = Product::factory()->create();
    $empanada = Product::factory()->create();
    $action = app(UpsertSaleAction::class);

    $sale = $action->execute([
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 280.00,
    ], [
        [
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
        [
            'product_id' => $empanada->id,
            'quantity' => 2,
            'unit_price' => 40.00,
            'applied_tax' => 0.00,
            'sub_total' => 80.00,
        ],
    ], null);

    $sale = $sale->fresh();

    $gallopintoDetail = $sale->saleDetails()
        ->where('product_id', '=', $gallopinto->id)
        ->firstOrFail(['*']);

    $empanadaDetail = $sale->saleDetails()
        ->where('product_id', '=', $empanada->id)
        ->firstOrFail(['*']);

    $action->execute([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 280.00,
    ], [
        [
            'id' => $gallopintoDetail->id,
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
    ], null);

    $this->assertSoftDeleted('sale_details', ['id' => $empanadaDetail->id]);

    $action->execute([
        'id' => $sale->id,
        'payment_status' => PaymentStatus::PAID->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => 280.00,
    ], [
        [
            'id' => $gallopintoDetail->id,
            'product_id' => $gallopinto->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'applied_tax' => 0.00,
            'sub_total' => 200.00,
        ],
        [
            'id' => $empanadaDetail->id,
            'product_id' => $empanada->id,
            'quantity' => 2,
            'unit_price' => 40.00,
            'applied_tax' => 0.00,
            'sub_total' => 80.00,
        ],
    ], null);

    $this->assertNotSoftDeleted('sale_details', ['id' => $empanadaDetail->id]);
    expect($sale->fresh()->saleDetails()->count('*'))->toBe(2);
});
