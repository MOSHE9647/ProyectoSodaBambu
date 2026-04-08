<?php

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
});

function createVerifiedAdminForHome(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create([
        'email_verified_at' => now(),
    ]);
}

/**
 * Epic: EIF-20_QA4 - Gestión de Personal
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-01_EIF-20_QA4 - renders dashboard with low stock and expiring counts', function () {
    // Given: a verified admin and dashboard data ready to be calculated.
    Cache::flush();
    $admin = createVerifiedAdminForHome();

    $product = Product::factory()->create();
    ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => 2,
        'minimum_stock' => 5,
    ]);

    $purchase = Purchase::factory()->create();
    $supply = Supply::factory()->create();
    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
        'purchasable_id' => $supply->id,
        'purchasable_type' => Supply::class,
        'expiration_date' => now()->addDays(3),
    ]);

    // When: the admin opens the dashboard.
    $response = $this->actingAs($admin)->get(route('dashboard'));

    // Then: the dashboard is rendered with the expected computed counters.
    $response
        ->assertSuccessful()
        ->assertViewIs('dashboard')
        ->assertViewHas('totalMinStockProducts', 1)
        ->assertViewHas('aboutToExpire', 1);
});

/**
 * Epic: EIF-20_QA4 - Gestión de Personal
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-02_EIF-20_QA4 - dashboard counts remain cached between requests', function () {
    // Given: a verified admin and initial dashboard data.
    Cache::flush();
    $admin = createVerifiedAdminForHome();

    $product = Product::factory()->create();
    ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => 1,
        'minimum_stock' => 5,
    ]);

    $purchase = Purchase::factory()->create();
    $supply = Supply::factory()->create();
    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
        'purchasable_id' => $supply->id,
        'purchasable_type' => Supply::class,
        'expiration_date' => now()->addDays(2),
    ]);

    // First request warms the cache.
    $this->actingAs($admin)->get(route('dashboard'))
        ->assertSuccessful()
        ->assertViewHas('totalMinStockProducts', 1)
        ->assertViewHas('aboutToExpire', 1);

    // New records are added after the cache has already been populated.
    ProductStock::withoutEvents(function () {
        $secondProduct = Product::factory()->create();

        ProductStock::factory()->create([
            'product_id' => $secondProduct->id,
            'current_stock' => 0,
            'minimum_stock' => 5,
        ]);
    });

    PurchaseDetail::withoutEvents(function () {
        $secondPurchase = Purchase::factory()->create();
        $secondSupply = Supply::factory()->create();

        PurchaseDetail::factory()->create([
            'purchase_id' => $secondPurchase->id,
            'purchasable_id' => $secondSupply->id,
            'purchasable_type' => Supply::class,
            'expiration_date' => now()->addDays(4),
        ]);
    });

    // When: the dashboard is requested again.
    $response = $this->actingAs($admin)->get(route('dashboard'));

    // Then: the cached values are still returned.
    $response
        ->assertSuccessful()
        ->assertViewHas('totalMinStockProducts', 1)
        ->assertViewHas('aboutToExpire', 1);
});
