<?php

use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Support\Facades\Validator;

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

// ==================== ClientRequest Rule Tests ====================

test('CP-RES-12 - client request requires first_name when creating', function () {
    // Given: a POST request missing first_name
    $request = new ClientRequest;
    $request->setMethod('POST');

    // When: we validate without first_name
    $validator = Validator::make(
        ['last_name' => 'García', 'email' => 'test@example.com'],
        $request->rules()
    );

    // Then: validation fails on first_name
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('first_name'))->toBeTrue();
});

test('CP-RES-13 - client request requires last_name when creating', function () {
    // Given: a POST request missing last_name
    $request = new ClientRequest;
    $request->setMethod('POST');

    // When: we validate without last_name
    $validator = Validator::make(
        ['first_name' => 'Juan', 'email' => 'test@example.com'],
        $request->rules()
    );

    // Then: validation fails on last_name
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('last_name'))->toBeTrue();
});

test('CP-RES-14 - client request requires email when creating', function () {
    // Given: a POST request missing email
    $request = new ClientRequest;
    $request->setMethod('POST');

    // When: we validate without email
    $validator = Validator::make(
        ['first_name' => 'Juan', 'last_name' => 'García'],
        $request->rules()
    );

    // Then: validation fails on email
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});

test('CP-RES-15 - client request rejects malformed email', function () {
    // Given: a POST request with an invalid email format
    $request = new ClientRequest;
    $request->setMethod('POST');

    // When: we validate with a malformed email
    $validator = Validator::make(
        ['first_name' => 'Juan', 'last_name' => 'García', 'email' => 'not-an-email'],
        $request->rules()
    );

    // Then: validation fails on email
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});

test('CP-RES-16 - client request accepts absent phone field', function () {
    // Given: a POST request with no phone (phone is nullable)
    $request = new ClientRequest;
    $request->setMethod('POST');

    // When: we validate without phone
    $validator = Validator::make(
        ['first_name' => 'Juan', 'last_name' => 'García', 'email' => 'juan@example.com'],
        $request->rules()
    );

    // Then: validation passes
    expect($validator->fails())->toBeFalse();
});

test('CP-RES-17 - client request rejects phone longer than 20 characters', function () {
    // Given: a POST request with a phone exceeding max length
    $request = new ClientRequest;
    $request->setMethod('POST');

    // When: we validate with a phone of 21 characters
    $validator = Validator::make(
        [
            'first_name' => 'Juan',
            'last_name'  => 'García',
            'email'      => 'juan@example.com',
            'phone'      => str_repeat('9', 21),
        ],
        $request->rules()
    );

    // Then: validation fails on phone
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('phone'))->toBeTrue();
});

test('CP-RES-18 - client request rejects duplicate email of an active client', function () {
    // Given: an active client with a known email
    Client::factory()->create(['email' => 'taken@example.com']);

    $request = new ClientRequest;
    $request->setMethod('POST');

    // When: we validate with the same email
    $validator = Validator::make(
        ['first_name' => 'Other', 'last_name' => 'Person', 'email' => 'taken@example.com'],
        $request->rules()
    );

    // Then: validation fails because the email is not unique among active clients
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
});
