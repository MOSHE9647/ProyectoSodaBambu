<?php

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supply;

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
test('CP-04_EIF-35 - purchase detail casts subtotal correctly', function () {
    // Given: a purchase detail with explicit subtotal value.
    $purchaseDetail = PurchaseDetail::factory()->create([
        'sub_total' => 617.25,
    ]);

    // When: reading the model attributes.
    // Then: subtotal is normalized as decimal string.
    expect((string) $purchaseDetail->sub_total)->toBe('617.25');
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Low
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-05_EIF-35 - purchase detail model is mass-assignable for fillable attributes', function () {
    // When: a purchase detail is created with mass assignment.
    $purchaseDetail = PurchaseDetail::factory()->create([
        'purchase_id' => Purchase::factory()->create()->id,
        'purchasable_id' => Product::factory()->create()->id,
        'purchasable_type' => Product::class,
        'sub_total' => 1750.00,
    ]);

    // Then: the attributes are persisted correctly.
    expect((string) $purchaseDetail->sub_total)->toBe('1750.00');
});
