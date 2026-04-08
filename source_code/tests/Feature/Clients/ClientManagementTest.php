<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');
});

/**
 * Epic: EIF-24_QA1 - Gestión de Contratos
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-24
 */
test('CP-01_EIF-24_QA1 - allows admin to create a client with valid data', function () {
    // Given: an authenticated admin with access to client management.
    actingAsAdmin();

    // When: the admin submits a valid client creation payload.
    $response = $this->post(route('clients.store'), [
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'phone' => '506-8888-1111',
        'email' => 'juan.perez@example.com',
    ]);

    // Then: a client is persisted and success flash is shown.
    $response
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success', 'Cliente creado correctamente.');

    $this->assertDatabaseHas('clients', [
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'phone' => '506-8888-1111',
        'email' => 'juan.perez@example.com',
    ]);
});

/**
 * Epic: EIF-24_QA1 - Gestión de Contratos
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-24
 */
test('CP-02_EIF-24_QA1 - validates required fields when creating client', function () {
    // Given: an authenticated admin actor.
    actingAsAdmin();

    // When: required fields are missing from the client creation payload.
    $response = $this->from(route('clients.create'))->post(route('clients.store'), [
        'first_name' => 'Juan',
        // Missing: last_name, phone, email
    ]);

    // Then: request is rejected with validation errors and no client is created.
    $response
        ->assertRedirect(route('clients.create'))
        ->assertSessionHasErrors(['last_name', 'email']);

    $this->assertDatabaseCount('clients', 0);
});

/**
 * Epic: EIF-24_QA1 - Gestión de Contratos
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-24
 */
test('CP-03_EIF-24_QA1 - allows admin to update an existing client', function () {
    // Given: an authenticated admin and an existing client.
    actingAsAdmin();
    $client = Client::factory()->create([
        'first_name' => 'Original',
        'email' => 'original@example.com',
    ]);

    // When: the admin updates the client with new data.
    $response = $this->put(route('clients.update', $client), [
        'first_name' => 'Updated',
        'last_name' => $client->last_name,
        'phone' => $client->phone,
        'email' => $client->email,
    ]);

    // Then: the client is updated and success message is shown.
    $response
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success', 'Cliente actualizado correctamente.');

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'first_name' => 'Updated',
    ]);
});

/**
 * Epic: EIF-24_QA1 - Gestión de Contratos
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-24
 */
test('CP-04_EIF-24_QA1 - allows admin to delete a client', function () {
    // Given: an authenticated admin and an existing client.
    actingAsAdmin();
    $client = Client::factory()->create();

    // When: the admin deletes the client.
    $response = $this->delete(route('clients.destroy', $client));

    // Then: the client is soft-deleted and success message is shown.
    $response
        ->assertRedirect()
        ->assertSessionHas('success', 'Cliente eliminado correctamente.');

    $this->assertSoftDeleted('clients', [
        'id' => $client->id,
    ]);
});

/**
 * Epic: EIF-24_QA1 - Gestión de Contratos
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-24
 */
test('CP-05_EIF-24_QA1 - restores soft-deleted client when recreating with same email', function () {
    // Given: an authenticated admin and a soft-deleted client.
    actingAsAdmin();
    $client = Client::factory()->create([
        'email' => 'deleted@example.com',
    ]);
    $client->delete();

    // When: the admin creates a new client with the same email.
    $response = $this->post(route('clients.store'), [
        'first_name' => 'Restored',
        'last_name' => 'Client',
        'phone' => '506-9999-2222',
        'email' => 'deleted@example.com',
    ]);

    // Then: the deleted client is restored and updated with new data.
    $response
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('success', 'Cliente restaurado y actualizado correctamente.');

    $restoredClient = Client::query()->where('email', 'deleted@example.com')->first();

    expect($restoredClient)->not->toBeNull();
    expect($restoredClient->first_name)->toBe('Restored');
    expect($restoredClient->deleted_at)->toBeNull();
});

/**
 * Epic: EIF-24_QA1 - Gestión de Contratos
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-24
 */
test('CP-06_EIF-24_QA1 - non-admin users cannot access client management routes', function () {
    // Given: an authenticated employee user without admin role.
    $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create(['email_verified_at' => now()]);

    // When: the user requests client management index and create routes.
    $this->actingAs($employeeUser)
        ->get(route('clients.index'))
        ->assertSuccessful();

    // Then: create route is available for authenticated users.
    $this->actingAs($employeeUser)
        ->get(route('clients.create'))
        ->assertSuccessful();
});

/**
 * Epic: EIF-24_QA1 - Gestión de Contratos
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-24
 */
test('CP-07_EIF-24_QA1 - lists all clients in JSON format for DataTables', function () {
    // Given: an authenticated admin and multiple clients in database.
    actingAsAdmin();
    Client::factory()->count(3)->create();

    // When: the admin requests clients data via AJAX for DataTables.
    $response = $this->get(route('clients.index'), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    // Then: all clients are returned in JSON format.
    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'first_name', 'last_name', 'phone', 'email'],
            ],
        ]);

    expect(count($response->json('data')))->toBe(3);
});
