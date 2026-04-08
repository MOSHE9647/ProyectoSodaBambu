<?php

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supply;
use Illuminate\Support\Carbon;

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-01_EIF-35 - purchase detail model is soft-deletable', function () {
    // Given: a created purchase detail.
    $purchaseDetail = PurchaseDetail::factory()->create();

    // When: the purchase detail is deleted.
    $purchaseDetail->delete();

    // Then: the purchase detail is soft-deleted.
    expect($purchaseDetail->trashed())->toBeTrue();
    expect(PurchaseDetail::query()->find($purchaseDetail->id))->toBeNull();
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-02_EIF-35 - purchase detail belongs to purchase', function () {
    // Given: a purchase detail linked to a purchase.
    $purchase = Purchase::factory()->create();
    $purchaseDetail = PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
    ]);

    // When: accessing the purchase relationship.
    $relatedPurchase = $purchaseDetail->purchase;

    // Then: the assigned purchase is returned.
    expect($relatedPurchase->id)->toBe($purchase->id);
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-03_EIF-35 - purchase detail morphs to purchasable model', function () {
    // Given: purchase details for a product and a supply.
    $product = Product::factory()->create();
    $supply = Supply::factory()->create();

    $productDetail = PurchaseDetail::factory()->create([
        'purchasable_id' => $product->id,
        'purchasable_type' => Product::class,
    ]);

    $supplyDetail = PurchaseDetail::factory()->create([
        'purchasable_id' => $supply->id,
        'purchasable_type' => Supply::class,
    ]);

    // When: accessing the morph relationship.
    // Then: the correct purchasable model is returned in each case.
    expect($productDetail->purchasable->is($product))->toBeTrue();
    expect($supplyDetail->purchasable->is($supply))->toBeTrue();
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-04_EIF-35 - purchase detail casts expiration date and money fields correctly', function () {
    // Given: a purchase detail with explicit casted values.
    $purchaseDetail = PurchaseDetail::factory()->create([
        'expiration_date' => '2026-03-20',
        'quantity' => 5,
        'unit_price' => 123.45,
        'subtotal' => 617.25,
    ]);

    // When: reading the model attributes.
    // Then: expiration date is a Carbon date and amount fields are normalized.
    expect($purchaseDetail->expiration_date)->toBeInstanceOf(Carbon::class);
    expect((string) $purchaseDetail->unit_price)->toBe('123.45');
    expect((string) $purchaseDetail->subtotal)->toBe('617.25');
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Low
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-05_EIF-35 - purchase detail model is mass-assignable for fillable attributes', function () {
    // When: a purchase detail is created with mass assignment.
    $purchaseDetail = PurchaseDetail::create([
        'purchase_id' => Purchase::factory()->create()->id,
        'purchasable_id' => Product::factory()->create()->id,
        'purchasable_type' => Product::class,
        'quantity' => 7,
        'unit_price' => 250.00,
        'subtotal' => 1750.00,
        'expiration_date' => '2026-04-01',
    ]);

    // Then: the attributes are persisted correctly.
    expect($purchaseDetail->quantity)->toBe(7);
    expect((string) $purchaseDetail->unit_price)->toBe('250.00');
    expect((string) $purchaseDetail->subtotal)->toBe('1750.00');
});
