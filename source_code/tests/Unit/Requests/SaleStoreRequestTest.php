<?php

use App\Enums\PaymentStatus;
use App\Http\Requests\SaleStoreRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

function saleStorePayload(float $total): array
{
    $product = Product::factory()->create();

    return [
        'payment_status' => PaymentStatus::PENDING->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => $total,
        'sale_details' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 50.00,
                'applied_tax' => 0.00,
                'sub_total' => 100.00,
            ],
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 25.00,
                'applied_tax' => 0.00,
                'sub_total' => 25.00,
            ],
        ],
    ];
}

function validateSaleStoreRequest(array $payload)
{
    $request = SaleStoreRequest::create('/', 'POST', $payload);
    $validator = Validator::make($request->all(), $request->rules());

    foreach ($request->after() as $callback) {
        $validator->after($callback);
    }

    $validator->passes();

    return $validator;
}

test('sale store request accepts totals that match the sum of detail subtotals', function () {
    $validator = validateSaleStoreRequest(saleStorePayload(125.00));

    expect($validator->errors()->isEmpty())->toBeTrue();
});

test('sale store request rejects totals that do not match the sum of detail subtotals', function () {
    $validator = validateSaleStoreRequest(saleStorePayload(124.99));

    expect($validator->errors()->has('total'))->toBeTrue();
    expect($validator->errors()->first('total'))->toBe('El total debe ser igual a la suma de los subtotales de los detalles de la venta.');
});
