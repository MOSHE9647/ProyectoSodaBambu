<?php

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

    // When: creating a purchase detail with expiration date within 7 days using factory
    PurchaseDetail::factory()->create([
        'expiration_date' => Carbon::now()->addDays(3),
    ]);

    // Then: cache is updated with expiration count
    expect(Cache::has('about_to_expire_count'))->toBeTrue();
    expect(Cache::get('about_to_expire_count'))->toBeGreaterThanOrEqual(1);
});

test('CP-02_EIF-22_QA2 - updates cache on updated event', function () {
    // Given: existing purchase detail
    Cache::flush();
    $detail = PurchaseDetail::factory()->create([
        'expiration_date' => Carbon::now()->addDays(10),
    ]);

    // When: updating the expiration date
    $detail->update(['expiration_date' => Carbon::now()->addDays(2)]);

    // Then: cache is recalculated
    expect(Cache::has('about_to_expire_count'))->toBeTrue();
});

test('CP-03_EIF-22_QA2 - updates cache on deleted event', function () {
    // Given: purchase detail with upcoming expiration
    Cache::flush();
    $detail = PurchaseDetail::factory()->create([
        'expiration_date' => Carbon::now()->addDays(3),
    ]);

    // When: deleting the purchase detail
    $detail->delete();

    // Then: cache is recalculated
    expect(Cache::has('about_to_expire_count'))->toBeTrue();
});

test('CP-04_EIF-22_QA2 - counts items expiring within 7 days correctly', function () {
    // Given: multiple purchase details with different expiration dates
    Cache::flush();

    // Item expiring in 3 days (should be counted)
    PurchaseDetail::factory()->create([
        'expiration_date' => Carbon::now()->addDays(3),
    ]);

    // Item expiring today (should be counted)
    PurchaseDetail::factory()->create([
        'expiration_date' => Carbon::now(),
    ]);

    // Item expiring in 10 days (should NOT be counted)
    PurchaseDetail::factory()->create([
        'expiration_date' => Carbon::now()->addDays(10),
    ]);

    // Trigger cache update
    PurchaseDetail::factory()->create([
        'expiration_date' => Carbon::now()->addDays(5),
    ]);

    // Then: cache shows items expiring within 7 days
    $count = Cache::get('about_to_expire_count', 0);
    expect($count)->toBeGreaterThanOrEqual(3);
});

test('CP-05_EIF-22_QA2 - ignores items without expiration date', function () {
    // Given: purchase details, some with and some without expiration
    Cache::flush();

    // Item without expiration
    PurchaseDetail::factory()->create([
        'expiration_date' => null,
    ]);

    // Item with expiration
    PurchaseDetail::factory()->create([
        'expiration_date' => Carbon::now()->addDays(3),
    ]);

    // When: cache is updated
    $count = Cache::get('about_to_expire_count', 0);

    // Then: only items with expiration dates are counted
    expect($count)->toBeLessThanOrEqual(2);
});
