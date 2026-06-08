<?php

use App\Enums\UserRole;
use App\Models\Client;
use Spatie\Permission\Models\Role;

/**
 * User Story: CP-API - Validar endpoint JSON de creación de clientes.
 * Epic: EIF-24_QA1 - Gestión de Clientes
 * Priority: High
 *
 * El controlador devuelve JSON cuando la petición lleva Accept: application/json
 * (satisface $request->wantsJson()). Estos tests validan la respuesta JSON del
 * endpoint clients.store en los escenarios de éxito, error de validación,
 * email duplicado y restauración de cliente eliminado.
 */
beforeEach(function (): void {
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');
});

test('CP-API-01 - store endpoint returns JSON with success flag and client data on valid request', function () {
    // Given: an authenticated admin.
    actingAsAdmin();

    // When: the admin sends a valid JSON creation request.
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('clients.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Torres',
            'phone' => '506-7777-8888',
            'email' => 'ana.torres@example.com',
        ]);

    // Then: the response is 200 with the expected JSON structure.
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Cliente creado correctamente.',
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'client' => ['id', 'name'],
        ]);

    // And: the client is persisted in the database.
    $this->assertDatabaseHas('clients', ['email' => 'ana.torres@example.com']);
});

test('CP-API-02 - store endpoint returns 422 with field errors when required fields are missing', function () {
    // Given: an authenticated admin.
    actingAsAdmin();

    // When: the admin sends a request missing last_name and email.
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('clients.store'), [
            'first_name' => 'Ana',
            // last_name and email are intentionally omitted
        ]);

    // Then: the response is 422 Unprocessable Entity with validation errors for the missing fields.
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['last_name', 'email']);
});

test('CP-API-03 - store endpoint returns 422 when email already belongs to an active client', function () {
    // Given: an authenticated admin and an active client with a known email.
    actingAsAdmin();
    Client::factory()->create(['email' => 'duplicate@example.com']);

    // When: the admin tries to create another client with the same email.
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('clients.store'), [
            'first_name' => 'Otro',
            'last_name' => 'Cliente',
            'email' => 'duplicate@example.com',
        ]);

    // Then: the request is rejected with a validation error on the email field.
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('CP-API-04 - store endpoint restores a soft-deleted client and returns the restoration message', function () {
    // Given: an authenticated admin and a soft-deleted client.
    actingAsAdmin();
    $client = Client::factory()->create(['email' => 'deleted@example.com']);
    $client->delete();

    // When: the admin creates a new client with the deleted client's email.
    $response = $this->withHeaders(['Accept' => 'application/json'])
        ->post(route('clients.store'), [
            'first_name' => 'Restaurado',
            'last_name' => 'Cliente',
            'email' => 'deleted@example.com',
        ]);

    // Then: the response confirms the client was restored with the updated data.
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Cliente restaurado y actualizado correctamente.',
        ]);

    // And: the client is no longer soft-deleted.
    expect(
        Client::query()->where('email', 'deleted@example.com')->first()->deleted_at
    )->toBeNull();
});
