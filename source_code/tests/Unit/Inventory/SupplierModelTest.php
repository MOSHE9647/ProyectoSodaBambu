<?php

use App\Models\Purchase;
use App\Models\Supplier;

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-01_EIF-49 - supplier model is soft-deletable', function () {
    // Given: a created supplier.
    $supplier = Supplier::factory()->create([
        'name' => 'Test Supplier',
        'email' => 'test@supplier.com',
    ]);

    // When: the supplier is deleted.
    $supplier->delete();

    // Then: the supplier is soft-deleted (not removed from DB).
    expect($supplier->trashed())->toBeTrue();

    // And: supplier is excluded from default queries.
    expect(Supplier::query()->find($supplier->id))->toBeNull();
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-02_EIF-49 - soft-deleted supplier can be restored', function () {
    // Given: a soft-deleted supplier.
    $supplier = Supplier::factory()->create();
    $supplier->delete();

    // When: the supplier is restored.
    $supplier->restore();

    // Then: the supplier is no longer trashed.
    expect($supplier->trashed())->toBeFalse();

    // And: supplier is included in default queries.
    expect(Supplier::query()->find($supplier->id))->not->toBeNull();
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-03_EIF-49 - supplier has many purchases relationship', function () {
    // Given: a supplier with multiple purchases.
    $supplier = Supplier::factory()->create();
    Purchase::factory()->count(3)->create(['supplier_id' => $supplier->id]);

    // When: accessing the purchases relationship.
    $purchases = $supplier->purchases;

    // Then: all related purchases are returned.
    expect($purchases)->toHaveCount(3);
    expect($purchases->every(fn ($p) => $p->supplier_id === $supplier->id))->toBeTrue();
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-04_EIF-49 - supplier model is mass-assignable for fillable attributes', function () {
    // When: a supplier is created with mass assignment.
    $supplier = Supplier::create([
        'name' => 'New Supplier',
        'phone' => '506-1234-5678',
        'email' => 'supplier@example.com',
    ]);

    // Then: all attributes are persisted correctly.
    expect($supplier->name)->toBe('New Supplier');
    expect($supplier->phone)->toBe('506-1234-5678');
    expect($supplier->email)->toBe('supplier@example.com');
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Low
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-05_EIF-49 - supplier timestamps are tracked', function () {
    // When: a supplier is created.
    $supplier = Supplier::factory()->create();
    $createdAt = $supplier->created_at;

    // Then: created_at timestamp exists.
    expect($createdAt)->not->toBeNull();

    // When: the supplier is updated.
    $supplier->update(['name' => 'Updated Supplier']);
    $updatedAt = $supplier->updated_at;

    // Then: updated_at is at or after created_at.
    expect($updatedAt->greaterThanOrEqualTo($createdAt))->toBeTrue();
});
