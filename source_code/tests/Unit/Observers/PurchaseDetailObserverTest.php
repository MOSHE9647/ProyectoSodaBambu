<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Unit Story: Purchase Detail Observer
 * Tests the PurchaseDetailObserver to ensure expiration date tracking
 * and cache updates work correctly.
 */
test('CP-01_EIF-22_QA2 - updates expiration cache on created event', function () {
    // Given: fresh cache state
    Cache::flush();

    $product = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'expiration_date' => Carbon::now()->addDays(3),
        'expiration_alert_days' => 7,
    ]);

    ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => 5,
        'minimum_stock' => 1,
    ]);

    // When: creating a purchase detail (observer executes cache refresh action)
    PurchaseDetail::factory()->create([
        'purchase_id' => Purchase::factory()->create()->id,
        'purchasable_id' => $product->id,
        'purchasable_type' => Product::class,
    ]);

    // Then: cache is updated with expiration count
    expect(Cache::has('about_to_expire_products_count'))->toBeTrue();
    expect(Cache::get('about_to_expire_products_count'))->toBe(1);
});

test('CP-02_EIF-22_QA2 - updates cache on updated event', function () {
    // Given: existing purchase detail
    Cache::flush();

    $product = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'expiration_date' => Carbon::now()->addDays(10),
        'expiration_alert_days' => 7,
    ]);

    ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => 5,
        'minimum_stock' => 1,
    ]);

    $detail = PurchaseDetail::factory()->create([
        'purchase_id' => Purchase::factory()->create()->id,
        'purchasable_id' => $product->id,
        'purchasable_type' => Product::class,
    ]);

    // When: updating the purchase detail
    $detail->update(['subtotal' => 999.99]);

    // Then: cache is recalculated
    expect(Cache::has('about_to_expire_products_count'))->toBeTrue();
    expect(Cache::get('about_to_expire_products_count'))->toBe(0);
});

test('CP-03_EIF-22_QA2 - updates cache on deleted event', function () {
    // Given: purchase detail with upcoming expiration
    Cache::flush();

    $product = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'expiration_date' => Carbon::now()->addDays(3),
        'expiration_alert_days' => 7,
    ]);

    ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => 5,
        'minimum_stock' => 1,
    ]);

    $detail = PurchaseDetail::factory()->create([
        'purchase_id' => Purchase::factory()->create()->id,
        'purchasable_id' => $product->id,
        'purchasable_type' => Product::class,
    ]);

    // When: deleting the purchase detail
    $detail->delete();

    // Then: cache is recalculated
    expect(Cache::has('about_to_expire_products_count'))->toBeTrue();
    expect(Cache::get('about_to_expire_products_count'))->toBe(1);
});

test('CP-04_EIF-22_QA2 - counts products expiring within 7 days correctly', function () {
    // Given: multiple products with different expiration dates
    Cache::flush();

    $expiringProductA = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'expiration_date' => Carbon::now()->addDays(3),
        'expiration_alert_days' => 7,
    ]);

    ProductStock::factory()->create([
        'product_id' => $expiringProductA->id,
        'current_stock' => 2,
        'minimum_stock' => 1,
    ]);

    $expiringProductB = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'expiration_date' => Carbon::now(),
        'expiration_alert_days' => 7,
    ]);

    ProductStock::factory()->create([
        'product_id' => $expiringProductB->id,
        'current_stock' => 3,
        'minimum_stock' => 1,
    ]);

    $nonExpiringProduct = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'expiration_date' => Carbon::now()->addDays(10),
        'expiration_alert_days' => 7,
    ]);

    ProductStock::factory()->create([
        'product_id' => $nonExpiringProduct->id,
        'current_stock' => 4,
        'minimum_stock' => 1,
    ]);

    // Trigger observer execution
    PurchaseDetail::factory()->create([
        'purchase_id' => Purchase::factory()->create()->id,
        'purchasable_id' => $expiringProductA->id,
        'purchasable_type' => Product::class,
    ]);

    // Then: cache shows products expiring within their alert threshold
    $count = Cache::get('about_to_expire_products_count', 0);
    expect($count)->toBe(2);
});

test('CP-05_EIF-22_QA2 - ignores products without expiration date', function () {
    // Given: products, some with and some without expiration
    Cache::flush();

    $withoutExpiration = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'expiration_date' => null,
    ]);

    ProductStock::factory()->create([
        'product_id' => $withoutExpiration->id,
        'current_stock' => 8,
        'minimum_stock' => 1,
    ]);

    $withExpiration = Product::factory()->create([
        'category_id' => Category::factory()->create()->id,
        'expiration_date' => Carbon::now()->addDays(3),
        'expiration_alert_days' => 7,
    ]);

    ProductStock::factory()->create([
        'product_id' => $withExpiration->id,
        'current_stock' => 8,
        'minimum_stock' => 1,
    ]);

    PurchaseDetail::factory()->create([
        'purchase_id' => Purchase::factory()->create()->id,
        'purchasable_id' => $withExpiration->id,
        'purchasable_type' => Product::class,
    ]);

    // When: cache is updated
    $count = Cache::get('about_to_expire_products_count', 0);

    // Then: only items with expiration dates are counted
    expect($count)->toBe(1);
});
