<?php

use App\Actions\Products\GetTopSellingProductsAction;
use App\Enums\PaymentStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;


test('CP-01_EIF-42 - calculates top selling products correctly based on paid sales', function () {
    // Given: an authenticated admin and existing products.
    actingAsAdmin(); 

    $productoA = Product::factory()->create(['name' => 'Casado']);
    $productoB = Product::factory()->create(['name' => 'Empanada']);

    // And: a PAID sale for product A.
    $ventaPagada = Sale::factory()->create(['payment_status' => PaymentStatus::PAID]);
    SaleDetail::factory()->create([
        'sale_id' => $ventaPagada->id,
        'product_id' => $productoA->id,
        'quantity' => 5,
        'sub_total' => 15000 // 5 x 3000
    ]);

    // And: a PENDING sale for product B (should be ignored).
    $ventaPendiente = Sale::factory()->create(['payment_status' => PaymentStatus::PENDING]);
    SaleDetail::factory()->create([
        'sale_id' => $ventaPendiente->id,
        'product_id' => $productoB->id,
        'quantity' => 10,
        'sub_total' => 10000
    ]);

    // When: executing the GetTopSellingProductsAction.
    $results = app(GetTopSellingProductsAction::class)->execute();

    // Then: only the paid sale data is returned.
    expect($results)->toHaveCount(1)
        ->and($results->first()['name'])->toBe('Casado')
        ->and((float) $results->first()['volume'])->toBe(5.0)
        ->and((float) $results->first()['revenue'])->toBe(15000.0);
});
