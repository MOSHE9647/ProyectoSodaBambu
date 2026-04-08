<?php

use App\Models\Product;
use App\Models\ProductStock;

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-01_EIF-32 - product stock model is soft-deletable', function () {
    // Given: a persisted product stock row.
    $productStock = ProductStock::factory()->create();

    // When: the stock row is deleted.
    $productStock->delete();

    // Then: it is soft-deleted and excluded from active queries.
    expect($productStock->trashed())->toBeTrue();
    expect(ProductStock::query()->find($productStock->id))->toBeNull();
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-02_EIF-32 - product stock belongs to product', function () {
    // Given: a stock row linked to a specific product.
    $product = Product::factory()->create();
    $stock = ProductStock::factory()->create([
        'product_id' => $product->id,
    ]);

    // When: resolving the relationship.
    $relatedProduct = $stock->product;

    // Then: the linked product is returned.
    expect($relatedProduct->id)->toBe($product->id);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-11_EIF-32 - low stock scope returns rows at or below minimum threshold', function () {
    // Given: stock rows with different inventory states.
    $low1 = ProductStock::factory()->create([
        'current_stock' => 2,
        'minimum_stock' => 5,
    ]);

    $low2 = ProductStock::factory()->create([
        'current_stock' => 5,
        'minimum_stock' => 5,
    ]);

    ProductStock::factory()->create([
        'current_stock' => 10,
        'minimum_stock' => 5,
    ]);

    // When: applying the low stock query scope.
    $ids = ProductStock::query()->lowStock()->pluck('id');

    // Then: only rows at or below minimum stock are included.
    expect($ids)->toContain($low1->id);
    expect($ids)->toContain($low2->id);
    expect($ids)->toHaveCount(2);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-03_EIF-32 - product stock casts quantity fields as integers', function () {
    // Given: a stock row with decimal-like numeric values.
    $stock = ProductStock::factory()->create([
        'current_stock' => 3.9,
        'minimum_stock' => 5.2,
    ]);

    // Then: values are cast to integers by the model.
    expect($stock->current_stock)->toBeInt();
    expect($stock->minimum_stock)->toBeInt();
});
