<?php

use App\Enums\UserRole;
use App\Http\Resources\RoleResource;
use Spatie\Permission\Models\Role;

/**
 * User Story: EIF-20_QA2 - Validar serialización de recursos de rol.
 * Epic: EIF-20_QA5 - Gestión de Usuarios y Autenticación
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-20
 */
test('CP-RES-12 - role resource returns id and name', function () {
    // Given: a role created with admin name
    $role = Role::create(['name' => UserRole::ADMIN->value]);

    // When: we convert the role to a resource
    $resource = new RoleResource($role);
    $array = $resource->resolve();

    // Then: the resource includes id and name
    expect($array)
        ->toHaveKey('id', $role->id)
        ->toHaveKey('name', UserRole::ADMIN->value);
});

test('CP-RES-13 - role resource collection transforms multiple roles', function () {
    // Given: multiple roles created
    Role::create(['name' => UserRole::ADMIN->value]);
    Role::create(['name' => UserRole::EMPLOYEE->value]);
    Role::create(['name' => UserRole::GUEST->value]);

    // When: we convert all roles to resources
    $roles = Role::all();
    $resources = RoleResource::collection($roles);
    $array = $resources->resolve();

    // Then: the collection includes all roles properly formatted
    expect($array)
        ->toHaveCount(3)
        ->and($array[0])->toHaveKey('name', UserRole::ADMIN->value)
        ->and($array[1])->toHaveKey('name', UserRole::EMPLOYEE->value)
        ->and($array[2])->toHaveKey('name', UserRole::GUEST->value);
});
