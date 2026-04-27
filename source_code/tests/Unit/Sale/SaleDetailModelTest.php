<?php

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

test('sale detail product relation is belongs to', function () {
    $relation = (new SaleDetail)->product();

    expect($relation)->toBeInstanceOf(BelongsTo::class);
});

test('sale detail resolves product using product id', function () {
    $sale = Sale::factory()->create();
    $product = Product::factory()->create([
        'has_inventory' => false,
    ]);

    $saleDetail = SaleDetail::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
    ]);

    expect($saleDetail->fresh()->product)->not->toBeNull()
        ->and($saleDetail->fresh()->product?->is($product))->toBeTrue();
});
