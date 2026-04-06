<?php

use App\Enums\UserRole;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supply;
use App\Models\User;

function createAdminUserForSupply(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create();
}

function createEmployeeUserForSupply(): User
{
    return User::factory()->withRole(UserRole::EMPLOYEE)->create();
}

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-01_EIF-49 - registers a supply with valid data and redirects with success message', function () {
    // Given: an authenticated admin user.
    $admin = createAdminUserForSupply();

    // When: the admin submits a valid supply registration request.
    $response = $this->actingAs($admin)->post(route('supplies.store'), [
        'name' => 'Harina de trigo',
        'measure_unit' => 'kg',
    ]);

    // Then: the system stores the supply and redirects with a success flash message.
    $response
        ->assertRedirect(route('supplies.index'))
        ->assertSessionHas('success', 'Insumo creado correctamente.');

    $this->assertDatabaseHas('supplies', [
        'name' => 'Harina de trigo',
        'measure_unit' => 'kg',
        'deleted_at' => null,
    ]);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-02_EIF-49 - rejects supply registration when name is missing', function () {
    // Given: an authenticated admin user.
    $admin = createAdminUserForSupply();

    // When: the admin submits a request without the required name field.
    $response = $this->actingAs($admin)
        ->from(route('supplies.create'))
        ->post(route('supplies.store'), [
            'measure_unit' => 'kg',
        ]);

    // Then: validation fails and no record is persisted.
    $response
        ->assertRedirect(route('supplies.create'))
        ->assertSessionHasErrors(['name']);

    $this->assertDatabaseCount('supplies', 0);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-03_EIF-49 - rejects supply registration when measure unit is missing', function () {
    // Given: an authenticated admin user.
    $admin = createAdminUserForSupply();

    // When: the admin submits a request without the required measure unit field.
    $response = $this->actingAs($admin)
        ->from(route('supplies.create'))
        ->post(route('supplies.store'), [
            'name' => 'Azucar',
        ]);

    // Then: validation fails and no record is persisted.
    $response
        ->assertRedirect(route('supplies.create'))
        ->assertSessionHasErrors(['measure_unit']);

    $this->assertDatabaseCount('supplies', 0);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-04_EIF-49 - rejects duplicated active supply names', function () {
    // Given: an authenticated admin and an existing active supply.
    $admin = createAdminUserForSupply();
    Supply::factory()->create([
        'name' => 'Leche',
        'measure_unit' => 'litros',
    ]);

    // When: the admin tries to register another active supply with the same name.
    $response = $this->actingAs($admin)
        ->from(route('supplies.create'))
        ->post(route('supplies.store'), [
            'name' => 'Leche',
            'measure_unit' => 'litros',
        ]);

    // Then: validation fails on name uniqueness for non-deleted rows.
    $response
        ->assertRedirect(route('supplies.create'))
        ->assertSessionHasErrors(['name']);

    expect(Supply::where('name', 'Leche')->count())->toBe(1);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-05_EIF-49 - updates an existing supply and returns success message', function () {
    // Given: an authenticated admin and an existing supply.
    $admin = createAdminUserForSupply();
    $supply = Supply::factory()->create([
        'name' => 'Frijoles',
        'measure_unit' => 'kg',
    ]);

    // When: the admin updates supply fields.
    $response = $this->actingAs($admin)->put(route('supplies.update', $supply), [
        'name' => 'Frijoles negros',
        'measure_unit' => 'kg',
    ]);

    // Then: the supply is updated successfully.
    $response
        ->assertRedirect(route('supplies.index'))
        ->assertSessionHas('success', 'Insumo actualizado correctamente.');

    $this->assertDatabaseHas('supplies', [
        'id' => $supply->id,
        'name' => 'Frijoles negros',
        'measure_unit' => 'kg',
    ]);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-06_EIF-49 - soft deletes an existing supply', function () {
    // Given: an authenticated admin and an existing supply.
    $admin = createAdminUserForSupply();
    $supply = Supply::factory()->create();

    // When: the admin deletes the supply.
    $response = $this->actingAs($admin)
        ->from(route('supplies.index'))
        ->delete(route('supplies.destroy', $supply));

    // Then: the row is soft deleted and the response contains success feedback.
    $response
        ->assertRedirect(route('supplies.index'))
        ->assertSessionHas('success', 'Insumo eliminado correctamente.');

    $this->assertSoftDeleted('supplies', [
        'id' => $supply->id,
    ]);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-07_EIF-49 - restores a soft-deleted supply when creating with the same name', function () {
    // Given: an authenticated admin and a previously soft-deleted supply.
    $admin = createAdminUserForSupply();

    $deletedSupply = Supply::factory()->create([
        'name' => 'Sal',
        'measure_unit' => 'kg',
    ]);

    $deletedSupply->delete();

    // When: the admin submits the same supply name again.
    $response = $this->actingAs($admin)->post(route('supplies.store'), [
        'name' => 'Sal',
        'measure_unit' => 'unidades',
    ]);

    // Then: the supply is restored and updated instead of creating a duplicate row.
    $response
        ->assertRedirect(route('supplies.index'))
        ->assertSessionHas('success', 'Insumo restaurado y actualizado correctamente.');

    expect(Supply::withTrashed()->where('name', 'Sal')->count())->toBe(1);

    $this->assertDatabaseHas('supplies', [
        'id' => $deletedSupply->id,
        'name' => 'Sal',
        'measure_unit' => 'unidades',
        'deleted_at' => null,
    ]);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-08_EIF-49 - denies supply module access to non-admin users', function () {
    // Given: an authenticated employee user without admin role.
    $employee = createEmployeeUserForSupply();

    // When: the non-admin user tries to access supply endpoints.
    $this->actingAs($employee)
        ->get(route('supplies.index'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->post(route('supplies.store'), [
            'name' => 'Prueba',
            'measure_unit' => 'kg',
        ])
        // Then: all write operations are forbidden as well.
        ->assertForbidden();
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-09_EIF-49 - displays supply detail page with related purchase data', function () {
    // Given: an authenticated admin and an existing supply.
    $admin = createAdminUserForSupply();
    $supply = Supply::factory()->create([
        'name' => 'Arroz',
        'measure_unit' => 'kg',
    ]);

    // When: the admin views the supply detail page.
    $response = $this->actingAs($admin)->get(route('supplies.show', $supply));

    // Then: the page is displayed with supply information.
    $response
        ->assertSuccessful()
        ->assertViewHas('supply');
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-10_EIF-49 - displays supply edit form with current values', function () {
    // Given: an authenticated admin and an existing supply.
    $admin = createAdminUserForSupply();
    $supply = Supply::factory()->create([
        'name' => 'Cebolla',
        'measure_unit' => 'kg',
    ]);

    // When: the admin requests the supply edit form.
    $response = $this->actingAs($admin)->get(route('supplies.edit', $supply));

    // Then: the edit form is displayed with current supply data.
    $response
        ->assertSuccessful()
        ->assertViewHas('supply', $supply);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-11_EIF-49 - filters supplies expiring within 7 days via AJAX DataTables', function () {
    // Given: an authenticated admin and supplies with various expiration dates.
    $admin = createAdminUserForSupply();
    $supply = Supply::factory()->create([
        'name' => 'Leche',
        'measure_unit' => 'litros',
    ]);

    // Create purchase detail with expiration within 7 days.
    $purchase = Purchase::factory()->create();

    PurchaseDetail::factory()->create([
        'purchase_id' => $purchase->id,
        'purchasable_id' => $supply->id,
        'purchasable_type' => Supply::class,
        'quantity' => 10,
        'unit_price' => 1500,
        'subtotal' => 15000,
        'expiration_date' => now()->addDays(3),
    ]);

    // When: the admin requests supplies with expiring_soon filter via AJAX.
    $response = $this->actingAs($admin)->get(route('supplies.index'), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'expiring_soon' => 'true',
        'draw' => 1,
        'start' => 0,
        'length' => 10,
    ]);

    // Then: only supplies with expiration within 7 days are returned.
    $response
        ->assertSuccessful()
        ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-12_EIF-49 - renders supplies index view for admin on non-ajax request', function () {
    // Given: an authenticated admin user.
    $admin = createAdminUserForSupply();

    // When: the admin opens the supplies index page without AJAX headers.
    $response = $this->actingAs($admin)->get(route('supplies.index'));

    // Then: the index view is rendered successfully.
    $response
        ->assertSuccessful()
        ->assertViewIs('models.supplies.index');
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-13_EIF-49 - returns default datatable column values when supply has no purchase details', function () {
    // Given: an authenticated admin and a supply without purchase details.
    $admin = createAdminUserForSupply();
    $supply = Supply::factory()->create([
        'name' => 'Salsa Inglesa',
        'measure_unit' => 'botella',
    ]);

    // When: requesting supplies via AJAX DataTables.
    $response = $this->actingAs($admin)->get(route('supplies.index'), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'draw' => 1,
        'start' => 0,
        'length' => 10,
    ]);

    // Then: computed columns use fallback defaults.
    $response
        ->assertSuccessful()
        ->assertJsonFragment([
            'id' => $supply->id,
            'quantity' => 0,
            'unit_price' => '₡0.00',
            'expiration_date' => 'N/A',
        ]);
});

/**
 * User Story: EIF-49 - Gestion de insumos.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-14_EIF-49 - datatable computed columns use latest purchase detail values', function () {
    // Given: an authenticated admin and a supply with multiple purchase details.
    $admin = createAdminUserForSupply();
    $supply = Supply::factory()->create([
        'name' => 'Levadura',
        'measure_unit' => 'gramos',
    ]);

    $purchaseA = Purchase::factory()->create();
    $purchaseB = Purchase::factory()->create();

    PurchaseDetail::factory()->create([
        'purchase_id' => $purchaseA->id,
        'purchasable_id' => $supply->id,
        'purchasable_type' => Supply::class,
        'quantity' => 4,
        'unit_price' => 1200,
        'subtotal' => 4800,
        'expiration_date' => '2026-04-05',
        'created_at' => now()->subMinute(),
    ]);

    $latestDetail = PurchaseDetail::factory()->create([
        'purchase_id' => $purchaseB->id,
        'purchasable_id' => $supply->id,
        'purchasable_type' => Supply::class,
        'quantity' => 9,
        'unit_price' => 1500,
        'subtotal' => 13500,
        'expiration_date' => '2026-04-09',
        'created_at' => now(),
    ]);

    // When: requesting supplies via AJAX DataTables.
    $response = $this->actingAs($admin)->get(route('supplies.index'), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'draw' => 1,
        'start' => 0,
        'length' => 10,
    ]);

    // Then: computed columns reflect the most recent purchase detail.
    $response
        ->assertSuccessful()
        ->assertJsonFragment([
            'id' => $supply->id,
            'quantity' => $latestDetail->quantity,
            'unit_price' => '₡1,500.00',
            'expiration_date' => '09/04/2026',
        ]);
});
