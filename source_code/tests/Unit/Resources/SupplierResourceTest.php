<?php

use App\Http\Resources\SupplierResource;
use App\Models\Supplier;

/**
 * User Story: EIF-35 - Validar serialización de recursos de proveedor.
 * Epic: EIF-22_QA5 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-35
 */
test('CP-RES-14 - supplier resource returns all supplier attributes', function () {
    // Given: a supplier with name, phone and email
    $supplier = Supplier::factory()->create([
        'name' => 'Acme Corp',
        'phone' => '+506 2222 3333',
        'email' => 'contact@acme.com',
    ]);

    // When: we convert the supplier to a resource
    $resource = new SupplierResource($supplier);
    $array = $resource->resolve();

    // Then: the resource includes all required fields
    expect($array)
        ->toHaveKey('id', $supplier->id)
        ->toHaveKey('name', 'Acme Corp')
        ->toHaveKey('phone', '+506 2222 3333')
        ->toHaveKey('email', 'contact@acme.com')
        ->toHaveKey('created_at', $supplier->created_at)
        ->toHaveKey('updated_at', $supplier->updated_at);
});

test('CP-RES-15 - supplier resource includes phone and email fields', function () {
    // Given: a supplier with all fields
    $supplier = Supplier::factory()->create();

    // When: we convert to resource
    $resource = new SupplierResource($supplier);
    $array = $resource->resolve();

    // Then: phone and email are present
    expect($array)
        ->toHaveKey('phone', $supplier->phone)
        ->toHaveKey('email', $supplier->email);
});
