<?php

use App\Http\Resources\CategoryResource;
use App\Models\Category;

/**
 * User Story: EIF-32 - Validar serialización de recursos de categoría.
 * Epic: EIF-22_QA5 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-RES-06 - category resource returns all category attributes', function () {
    // Given: a category with name and description
    $category = Category::factory()->create([
        'name' => 'Electronics',
        'description' => 'Electronic products',
    ]);

    // When: we convert the category to a resource
    $resource = new CategoryResource($category);
    $array = $resource->resolve();

    // Then: the resource includes all required fields
    expect($array)
        ->toHaveKey('id', $category->id)
        ->toHaveKey('name', 'Electronics')
        ->toHaveKey('description', 'Electronic products')
        ->toHaveKey('created_at', $category->created_at)
        ->toHaveKey('updated_at', $category->updated_at);
});

test('CP-RES-07 - category resource handles null description', function () {
    // Given: a category without description
    $category = Category::factory()->create([
        'description' => null,
    ]);

    // When: we convert to resource
    $resource = new CategoryResource($category);
    $array = $resource->resolve();

    // Then: description is null
    expect($array['description'])->toBeNull();
});
