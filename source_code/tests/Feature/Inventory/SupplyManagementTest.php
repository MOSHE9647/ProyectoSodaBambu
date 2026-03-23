<?php

use App\Enums\UserRole;
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
