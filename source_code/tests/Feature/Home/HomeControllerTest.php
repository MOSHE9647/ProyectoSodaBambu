<?php

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductStock;
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

    $product->update([
        'expiration_date' => now()->addDays(3),
        'expiration_alert_days' => 7,
    ]);

    Supply::factory()->create([
        'quantity' => 5,
        'unit_price' => 1200,
        'expiration_date' => now()->addDays(3),
        'expiration_alert_days' => 7,
    ]);

    // When: the admin opens the dashboard.
    $response = $this->actingAs($admin)->get(route('dashboard'));

    // Then: the dashboard is rendered with the expected computed counters.
    $response
        ->assertSuccessful()
        ->assertViewIs('dashboard')
        ->assertViewHas('totalMinStockProducts', 1)
        ->assertViewHas('aboutToExpireProducts', 1)
        ->assertViewHas('aboutToExpireSupplies', 1);
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

    $product->update([
        'expiration_date' => now()->addDays(2),
        'expiration_alert_days' => 7,
    ]);

    Supply::factory()->create([
        'quantity' => 5,
        'unit_price' => 1200,
        'expiration_date' => now()->addDays(2),
        'expiration_alert_days' => 7,
    ]);

    // First request warms the cache.
    $this->actingAs($admin)->get(route('dashboard'))
        ->assertSuccessful()
        ->assertViewHas('totalMinStockProducts', 1)
        ->assertViewHas('aboutToExpireProducts', 1)
        ->assertViewHas('aboutToExpireSupplies', 1);

    // New records are added after the cache has already been populated.
    ProductStock::withoutEvents(function () {
        $secondProduct = Product::factory()->create();

        ProductStock::factory()->create([
            'product_id' => $secondProduct->id,
            'current_stock' => 0,
            'minimum_stock' => 5,
        ]);
    });

    Product::withoutEvents(function () {
        $secondProduct = Product::factory()->create();
        $secondProduct->update([
            'expiration_date' => now()->addDays(4),
            'expiration_alert_days' => 7,
        ]);
    });

    Supply::withoutEvents(function () {
        Supply::factory()->create([
            'quantity' => 3,
            'unit_price' => 950,
            'expiration_date' => now()->addDays(4),
            'expiration_alert_days' => 7,
        ]);
    });

    // When: the dashboard is requested again.
    $response = $this->actingAs($admin)->get(route('dashboard'));

    // Then: the cached values are still returned.
    $response
        ->assertSuccessful()
        ->assertViewHas('totalMinStockProducts', 1)
        ->assertViewHas('aboutToExpireProducts', 1)
        ->assertViewHas('aboutToExpireSupplies', 1);
});
