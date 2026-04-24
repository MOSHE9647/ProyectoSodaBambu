<?php

use App\Enums\PaymentStatus;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use Illuminate\Support\Carbon;

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-01_EIF-35 - purchase model is soft-deletable', function () {
    // Given: a created purchase.
    $purchase = Purchase::factory()->create();

    // When: the purchase is deleted.
    $purchase->delete();

    // Then: the purchase is soft-deleted.
    expect($purchase->trashed())->toBeTrue();
    expect(Purchase::query()->find($purchase->id))->toBeNull();
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-02_EIF-35 - purchase belongs to supplier', function () {
    // Given: a purchase with an assigned supplier.
    $supplier = Supplier::factory()->create();
    $purchase = Purchase::factory()->create([
        'supplier_id' => $supplier->id,
    ]);

    // When: accessing the supplier relationship.
    $relatedSupplier = $purchase->supplier;

    // Then: the assigned supplier is returned.
    expect($relatedSupplier->id)->toBe($supplier->id);
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-03_EIF-35 - purchase has many purchase details', function () {
    // Given: a purchase with multiple details.
    $purchase = Purchase::factory()->create();
    PurchaseDetail::factory()->count(2)->create([
        'purchase_id' => $purchase->id,
    ]);

    // When: accessing the details relationship.
    $details = $purchase->details;

    // Then: all related purchase details are returned.
    expect($details)->toHaveCount(2);
    expect($details->every(fn ($detail) => $detail->purchase_id === $purchase->id))->toBeTrue();
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-04_EIF-35 - purchase casts payment status and date correctly', function () {
    // Given: a purchase created with explicit casted values.
    $purchase = Purchase::factory()->create([
        'date' => '2026-03-15 10:30:00',
        'payment_status' => PaymentStatus::PENDING,
        'total' => 1234.5,
    ]);

    // When: reading the model attributes.
    // Then: payment status is cast to enum and date is a date instance.
    expect($purchase->payment_status)->toBe(PaymentStatus::PENDING);
    expect($purchase->date)->toBeInstanceOf(Carbon::class);
    expect((string) $purchase->total)->toBe('1234.50');
});

/**
 * User Story: EIF-35 - Registrar Compras y proveedores de abastecimiento.
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Low
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-05_EIF-35 - purchase model is mass-assignable for fillable attributes', function () {
    // Given: a admin user for creating the purchase.
    actingAsAdmin();

    // When: a purchase is created with mass assignment.
    $purchase = Purchase::create([
        'user_id' => auth()->id(),
        'supplier_id' => Supplier::factory()->create()->id,
        'invoice_number' => 'INV-10001',
        'payment_status' => PaymentStatus::PAID,
        'date' => '2026-03-20 12:00:00',
        'total' => 2500.75,
    ]);

    // Then: the attributes are persisted correctly.
    expect($purchase->invoice_number)->toBe('INV-10001');
    expect($purchase->payment_status)->toBe(PaymentStatus::PAID);
    expect((string) $purchase->total)->toBe('2500.75');
});
