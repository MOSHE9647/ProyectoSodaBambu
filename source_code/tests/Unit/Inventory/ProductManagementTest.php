<?php

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-01_EIF-32 - calculateSalePrice applies tax and margin correctly', function () {
    // Given: a reference cost with decimal tax and margin percentages.
    $referenceCost = 10000.0;
    $taxPercentage = 0.13;
    $marginPercentage = 0.35;

    // When: the sale price is calculated.
    $salePrice = Product::calculateSalePrice($referenceCost, $taxPercentage, $marginPercentage);

    // Then: the expected final value is returned.
    expect($salePrice)->toBe(15255.0);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-02_EIF-32 - product type is cast to ProductType enum', function () {
    // Given: a product row with type value stored from enum case.
    $product = Product::factory()->create([
        'type' => ProductType::DISH->value,
    ]);

    // When: reading the model attribute.
    $type = $product->type;

    // Then: type is returned as a ProductType enum instance.
    expect($type)->toBeInstanceOf(ProductType::class)
        ->and($type)->toBe(ProductType::DISH);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-03_EIF-32 - product can be soft deleted and restored', function () {
    // Given: a persisted product.
    $product = Product::factory()->create();

    // When: soft deleting and restoring the product.
    $product->delete();
    $trashedProduct = Product::onlyTrashed()->find($product->id);

    expect($trashedProduct)->not->toBeNull();

    $trashedProduct->restore();

    // Then: product is available again in active scope.
    expect(Product::find($product->id))->not->toBeNull();
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-04_EIF-32 - product stock relation is a has one relation', function () {
    // Given: a product model instance.
    $product = new Product;

    // When: requesting the stock relationship object.
    $relation = $product->stock();

    // Then: relation is defined as has-one.
    expect($relation)->toBeInstanceOf(HasOne::class);
});
