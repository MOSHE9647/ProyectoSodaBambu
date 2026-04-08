<?php

use App\Http\Resources\SupplyResource;
use App\Models\Supply;
use Carbon\Carbon;

/**
 * User Story: EIF-49 - Validar serialización de recursos de suministro.
 * Epic: EIF-22_QA5 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-49
 */
test('CP-RES-16 - supply resource returns basic attributes', function () {
    // Given: a supply with name and measure_unit
    $supply = Supply::factory()->create([
        'name' => 'Flour',
        'measure_unit' => 'kg',
    ]);

    // When: we convert the supply to a resource
    $resource = new SupplyResource($supply);
    $array = $resource->resolve();

    // Then: the resource includes basic fields
    expect($array)
        ->toHaveKey('id', $supply->id)
        ->toHaveKey('name', 'Flour')
        ->toHaveKey('measure_unit', 'kg');
});

test('CP-RES-17 - supply resource formats created_at when DateTimeInterface', function () {
    // Given: a supply with a specific created_at
    $now = Carbon::now();
    $supply = Supply::factory()->create([
        'created_at' => $now,
    ]);

    // When: we refresh the model and convert to resource
    $supply->refresh();
    $resource = new SupplyResource($supply);
    $array = $resource->resolve();

    // Then: created_at is formatted as Y-m-d H:i:s
    expect($array['created_at'])
        ->toBe($now->format('Y-m-d H:i:s'));
});

test('CP-RES-18 - supply resource handles created_at when already formatted', function () {
    // Given: a supply created and then refreshed (has DateTimeInterface)
    $supply = Supply::factory()->create();
    $supply->refresh();

    // When: we convert to resource
    $resource = new SupplyResource($supply);
    $array = $resource->resolve();

    // Then: created_at is formatted as Y-m-d H:i:s
    expect($array['created_at'])
        ->toMatch('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');
});

test('CP-RES-19 - supply resource returns null when created_at is invalid', function () {
    // Given: a supply model with invalid created_at
    $supply = new Supply([
        'id' => 1,
        'name' => 'Salt',
        'measure_unit' => 'g',
        'created_at' => 123,
    ]);

    // When: we convert to resource
    $resource = new SupplyResource($supply);
    $array = $resource->resolve();

    // Then: created_at is null
    expect($array['created_at'])->toBeNull();
});
