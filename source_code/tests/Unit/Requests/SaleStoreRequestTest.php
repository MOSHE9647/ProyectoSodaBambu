<?php

use App\Enums\PaymentStatus;
use App\Http\Requests\SaleStoreRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

function saleStorePayload(int|float $total): array
{
    $firstProduct = Product::factory()->create(['has_inventory' => false]);
    $secondProduct = Product::factory()->create(['has_inventory' => false]);

    return [
        'payment_status' => PaymentStatus::PENDING->value,
        'date' => now()->format('Y-m-d H:i:s'),
        'total' => $total,
        'sale_details' => [
            [
                'product_id' => $firstProduct->id,
                'quantity' => 2,
                'unit_price' => 50,
                'applied_tax' => 0.00,
                'sub_total' => 100,
            ],
            [
                'product_id' => $secondProduct->id,
                'quantity' => 1,
                'unit_price' => 25,
                'applied_tax' => 0.00,
                'sub_total' => 25,
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
    $validator = validateSaleStoreRequest(saleStorePayload(125));

    expect($validator->errors()->isEmpty())->toBeTrue();
});

test('sale store request rejects totals that do not match the sum of detail subtotals', function () {
    $validator = validateSaleStoreRequest(saleStorePayload(124));

    expect($validator->errors()->has('total'))->toBeTrue();
    expect($validator->errors()->first('total'))->toBe('El total (124) no coincide con la suma de los productos (125).');
});

test('sale store request rejects decimal currency values', function () {
    $payload = saleStorePayload(125);
    $payload['total'] = 125.50;
    $payload['sale_details'][0]['unit_price'] = 50.25;
    $payload['sale_details'][0]['sub_total'] = 100.50;
    $payload['payment_status'] = PaymentStatus::PAID->value;
    $payload['payment_details'] = [
        [
            'method' => 'cash',
            'amount' => 125.50,
            'change_amount' => 0.25,
        ],
    ];

    $validator = validateSaleStoreRequest($payload);

    expect($validator->errors()->has('total'))->toBeTrue()
        ->and($validator->errors()->has('sale_details.0.unit_price'))->toBeTrue()
        ->and($validator->errors()->has('sale_details.0.sub_total'))->toBeTrue()
        ->and($validator->errors()->has('payment_details.0.amount'))->toBeTrue()
        ->and($validator->errors()->has('payment_details.0.change_amount'))->toBeTrue();
});

test('sale store request matches frontend line-level tax rounding', function () {
    $payload = saleStorePayload(12);
    $payload['sale_details'][0]['quantity'] = 1;
    $payload['sale_details'][0]['unit_price'] = 5;
    $payload['sale_details'][0]['applied_tax'] = 0.10;
    $payload['sale_details'][0]['sub_total'] = 5;
    $payload['sale_details'][1]['quantity'] = 1;
    $payload['sale_details'][1]['unit_price'] = 5;
    $payload['sale_details'][1]['applied_tax'] = 0.10;
    $payload['sale_details'][1]['sub_total'] = 5;

    $validator = validateSaleStoreRequest($payload);

    expect($validator->errors()->isEmpty())->toBeTrue();
});
