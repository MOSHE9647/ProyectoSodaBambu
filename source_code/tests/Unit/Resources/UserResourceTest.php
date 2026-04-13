<?php

use App\Http\Resources\UserResource;
use App\Models\Employee;
use App\Models\User;

/**
 * User Story: EIF-20_QA2 - Validar serialización de recursos de usuario.
 * Epic: EIF-20_QA5 - Gestión de Usuarios y Autenticación
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-RES-01 - user resource includes all user attributes', function () {
    // Given: a user with name and email
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // When: we convert the user to a resource
    $resource = new UserResource($user);
    $array = $resource->resolve();

    // Then: the resource includes all required fields
    expect($array)
        ->toHaveKey('id', $user->id)
        ->toHaveKey('name', 'John Doe')
        ->toHaveKey('email', 'john@example.com')
        ->toHaveKey('created_at')
        ->toHaveKey('updated_at')
        ->toHaveKey('deleted_at', null);
});

test('CP-RES-02 - user resource resolves user fields correctly', function () {
    // Given: a user
    $user = User::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
    ]);

    // When: we convert the user to a resource and resolve it
    $resource = new UserResource($user);
    $array = $resource->resolve();

    // Then: all core fields are resolved correctly
    expect($array)
        ->toHaveKey('id', $user->id)
        ->toHaveKey('name', 'Jane Smith')
        ->toHaveKey('email', 'jane@example.com')
        ->toHaveKey('created_at', $user->created_at)
        ->toHaveKey('updated_at', $user->updated_at);
});

test('CP-RES-03 - user resource excludes roles when not loaded', function () {
    // Given: a user without loaded roles
    $user = User::factory()->create();

    // When: we convert to resource without loading roles
    $resource = new UserResource($user);
    $array = $resource->resolve();

    // Then: roles key is not present when relation not loaded
    expect($array)->not->toHaveKey('roles');
});

test('CP-RES-04 - user resource excludes employee when not loaded', function () {
    // Given: a user without loaded employee relation
    $user = User::factory()->create();

    // When: we convert to resource
    $resource = new UserResource($user);
    $array = $resource->resolve();

    // Then: employee key is not present when relation not loaded
    expect($array)->not->toHaveKey('employee');
});

test('CP-RES-05 - user resource includes employee when loaded', function () {
    // Given: a user with an employee record
    $user = User::factory()->create();
    Employee::factory()->create([
        'id' => $user->id,
    ]);

    // When: we load the employee relation and convert to resource
    $user->load('employee');
    $resource = new UserResource($user);
    $array = $resource->resolve();

    // Then: the resource includes the employee
    expect($array['employee'])
        ->not->toBe((new UserResource(new User))->resolve())
        ->and($array['employee'])
        ->toHaveKey('id', $user->id);
});
