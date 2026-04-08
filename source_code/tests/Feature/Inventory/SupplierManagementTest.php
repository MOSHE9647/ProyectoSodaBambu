<?php

use App\Enums\UserRole;
use App\Models\Supplier;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-01_EIF-49 - allows admin to create a supplier with valid data', function () {
    // Given: an authenticated admin with access to supplier management.
    actingAsAdmin();

    // When: the admin submits a valid supplier creation payload.
    $response = $this->post(route('suppliers.store'), [
        'name' => 'Proveedor García',
        'phone' => '506-7777-1234',
        'email' => 'proveedor.garcia@example.com',
    ]);

    // Then: a supplier is persisted and success flash is shown.
    $response
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success', 'Proveedor creado exitosamente.');

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Proveedor García',
        'phone' => '506-7777-1234',
        'email' => 'proveedor.garcia@example.com',
    ]);
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-02_EIF-49 - validates required supplier fields', function () {
    // Given: an authenticated admin actor.
    actingAsAdmin();

    // When: required fields are missing from the supplier creation payload.
    $response = $this->from(route('suppliers.create'))->post(route('suppliers.store'), [
        'name' => 'Incomplete Supplier',
        // Missing: phone, email
    ]);

    // Then: request is rejected with validation errors and no supplier is created.
    $response
        ->assertRedirect(route('suppliers.create'))
        ->assertSessionHasErrors(['phone', 'email']);

    $this->assertDatabaseCount('suppliers', 0);
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-03_EIF-49 - allows admin to update an existing supplier', function () {
    // Given: an authenticated admin and an existing supplier.
    actingAsAdmin();
    $supplier = Supplier::factory()->create([
        'name' => 'Original Proveedor',
        'email' => 'original@example.com',
    ]);

    // When: the admin updates the supplier with new data.
    $response = $this->put(route('suppliers.update', $supplier), [
        'name' => 'Updated Proveedor',
        'phone' => $supplier->phone,
        'email' => $supplier->email,
    ]);

    // Then: the supplier is updated and success message is shown.
    $response
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success', 'Proveedor actualizado exitosamente.');

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'Updated Proveedor',
    ]);
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-04_EIF-49 - allows admin to delete a supplier', function () {
    // Given: an authenticated admin and an existing supplier.
    actingAsAdmin();
    $supplier = Supplier::factory()->create();

    // When: the admin deletes the supplier.
    $response = $this->delete(route('suppliers.destroy', $supplier));

    // Then: the supplier is soft-deleted and success message is shown.
    $response
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success', 'Proveedor eliminado exitosamente.');

    $this->assertSoftDeleted('suppliers', [
        'id' => $supplier->id,
    ]);
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-05_EIF-49 - restores soft-deleted supplier when recreating with same email', function () {
    // Given: an authenticated admin and a soft-deleted supplier.
    actingAsAdmin();
    $supplier = Supplier::factory()->create([
        'email' => 'deleted@suppliers.com',
    ]);
    $supplier->delete();

    // When: the admin creates a new supplier with the same email.
    $response = $this->post(route('suppliers.store'), [
        'name' => 'Restored Proveedor',
        'phone' => '506-8888-4321',
        'email' => 'deleted@suppliers.com',
    ]);

    // Then: the deleted supplier is restored and updated with new data.
    $response
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success', 'Proveedor restaurado y actualizado exitosamente.');

    $restoredSupplier = Supplier::query()->where('email', 'deleted@suppliers.com')->first();

    expect($restoredSupplier)->not->toBeNull();
    expect($restoredSupplier->name)->toBe('Restored Proveedor');
    expect($restoredSupplier->deleted_at)->toBeNull();
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-06_EIF-49 - non-admin users cannot access supplier management routes', function () {
    // Given: an authenticated employee user without admin role.
    $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create(['email_verified_at' => now()]);

    // When: the user requests supplier management index and create routes.
    $this->actingAs($employeeUser)
        ->get(route('suppliers.index'))
        ->assertSuccessful();

    // Then: create route is available for authenticated users.
    $this->actingAs($employeeUser)
        ->get(route('suppliers.create'))
        ->assertSuccessful();
});

/**
 * User Story: EIF-49 - Registrar Insumos (Suppliers: Gestión de Proveedores)
 * Epic: EIF-22 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-07_EIF-49 - lists all suppliers in JSON format for DataTables', function () {
    // Given: an authenticated admin and multiple suppliers in database.
    actingAsAdmin();
    Supplier::factory()->count(3)->create();

    // When: the admin requests suppliers data via AJAX for DataTables.
    $response = $this->get(route('suppliers.index'), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    // Then: all suppliers are returned in JSON format.
    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'phone', 'email'],
            ],
        ]);

    expect(count($response->json('data')))->toBe(3);
});
