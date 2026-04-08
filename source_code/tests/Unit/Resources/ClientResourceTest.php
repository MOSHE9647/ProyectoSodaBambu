<?php

use App\Http\Resources\ClientResource;
use App\Models\Client;

/**
 * User Story: EIF-20 - Validar serialización de recursos de cliente.
 * Epic: EIF-20_QA5 - Gestión de Usuarios y Autenticación
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-RES-08 - client resource concatenates first and last name', function () {
    // Given: a client with first_name, last_name and email
    $client = Client::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'juan@example.com',
    ]);

    // When: we convert the client to a resource
    $resource = new ClientResource($client);
    $array = $resource->resolve();

    // Then: the resource includes full_name concatenated
    expect($array)
        ->toHaveKey('first_name', 'Juan')
        ->toHaveKey('last_name', 'Pérez')
        ->toHaveKey('full_name', 'Juan Pérez')
        ->toHaveKey('email', 'juan@example.com');
});

test('CP-RES-09 - client resource returns N/A when phone is null', function () {
    // Given: a client without phone
    $client = Client::factory()->create([
        'phone' => null,
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
    ]);

    // When: we convert to resource
    $resource = new ClientResource($client);
    $array = $resource->resolve();

    // Then: phone is N/A
    expect($array['phone'])->toBe('N/A');
});

test('CP-RES-10 - client resource returns phone when present', function () {
    // Given: a client with phone
    $client = Client::factory()->create([
        'phone' => '+506 8765 4321',
        'first_name' => 'Carlos',
        'last_name' => 'Lopez',
    ]);

    // When: we convert to resource
    $resource = new ClientResource($client);
    $array = $resource->resolve();

    // Then: phone is included
    expect($array['phone'])->toBe('+506 8765 4321');
});

test('CP-RES-11 - client resource formats created_at as Y-m-d H:i:s', function () {
    // Given: a client with a specific created_at timestamp
    $now = now();
    $client = Client::factory()->create([
        'created_at' => $now,
        'first_name' => 'Ana',
        'last_name' => 'Martinez',
    ]);

    // When: we convert to resource
    $resource = new ClientResource($client);
    $array = $resource->resolve();

    // Then: created_at is formatted as Y-m-d H:i:s
    expect($array['created_at'])
        ->toBe($now->format('Y-m-d H:i:s'));
});
