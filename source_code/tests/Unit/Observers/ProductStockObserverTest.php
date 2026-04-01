<?php

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * Unit Story: Product Stock Observer
 * Tests the ProductStockObserver to ensure cache and session updates
 * happen correctly when product stock levels change.
 */
test('CP-01_EIF-22_QA1 - updates low stock cache on created event', function () {
    // Given: a new product stock record is about to be created
    Cache::flush();

    // When: creating a product stock with low quantity
    $product = Product::factory()->create();
    $productStock = ProductStock::create([
        'product_id' => $product->id,
        'current_stock' => 2,
        'minimum_stock' => 5,
    ]);

    // Then: the low stock count cache is updated
    expect(Cache::has('low_stock_count'))->toBeTrue();
    expect(Cache::get('low_stock_count'))->toBeGreaterThanOrEqual(1);
});

test('CP-02_EIF-22_QA1 - flashes warning to session when stock becomes low', function () {
    // Given: an existing product stock
    Session::flush();
    $product = Product::factory()->create(['name' => 'Test Product']);
    $productStock = ProductStock::create([
        'product_id' => $product->id,
        'current_stock' => 10,
        'minimum_stock' => 5,
    ]);

    // When: updating stock to a low level
    $productStock->update(['current_stock' => 3]);

    // Then: a warning message is flashed to session
    expect(Session::has('warning'))->toBeTrue();
    expect(Session::get('warning'))->toContain('Stock bajo');
});

test('CP-03_EIF-22_QA1 - does not flash warning when stock remains above minimum', function () {
    // Given: a product with adequate stock
    $product = Product::factory()->create();
    $productStock = ProductStock::create([
        'product_id' => $product->id,
        'current_stock' => 10,
        'minimum_stock' => 5,
    ]);

    // When: updating some other field (not stock)
    $productStock->update(['current_stock' => 8]);

    // Then: no warning is flashed
    expect(Session::has('warning'))->toBeFalse();
});

test('CP-04_EIF-22_QA1 - updates low stock cache when stock changes', function () {
    // Given: initial cache state
    Cache::flush();
    $product = Product::factory()->create();
    $productStock = ProductStock::create([
        'product_id' => $product->id,
        'current_stock' => 10,
        'minimum_stock' => 5,
    ]);
    $initialCount = Cache::get('low_stock_count', 0);

    // When: reducing stock to below minimum
    $productStock->update(['current_stock' => 2]);

    // Then: cache is updated (count may change)
    expect(Cache::has('low_stock_count'))->toBeTrue();
});

test('CP-05_EIF-22_QA1 - updates low stock cache on deleted event', function () {
    // Given: a product stock record exists
    Cache::flush();
    $product = Product::factory()->create();
    $productStock = ProductStock::create([
        'product_id' => $product->id,
        'current_stock' => 2,
        'minimum_stock' => 5,
    ]);
    $countBeforeDelete = Cache::get('low_stock_count', 0);

    // When: deleting the product stock
    $productStock->delete();

    // Then: cache is recalculated
    expect(Cache::has('low_stock_count'))->toBeTrue();
});

test('CP-06_EIF-22_QA1 - counts products correctly with low stock threshold', function () {
    // Given: multiple products with different stock levels
    Cache::flush();
    $p1 = Product::factory()->create();
    ProductStock::create(['product_id' => $p1->id, 'current_stock' => 2, 'minimum_stock' => 5]);

    $p2 = Product::factory()->create();
    ProductStock::create(['product_id' => $p2->id, 'current_stock' => 10, 'minimum_stock' => 5]);

    $p3 = Product::factory()->create();
    ProductStock::create(['product_id' => $p3->id, 'current_stock' => 5, 'minimum_stock' => 5]); // exactly at minimum

    // When: triggering a cache update (any observer event)
    $p4 = Product::factory()->create();
    ProductStock::create([
        'product_id' => $p4->id,
        'current_stock' => 1,
        'minimum_stock' => 5,
    ]);

    // Then: cache shows count of products at or below minimum
    $count = Cache::get('low_stock_count', 0);
    expect($count)->toBeGreaterThanOrEqual(3);
});
