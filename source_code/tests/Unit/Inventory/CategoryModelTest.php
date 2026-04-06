<?php

use App\Models\Category;
use App\Models\Product;

/**
 * Epic: EIF-22_QA2 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-01_EIF-22_QA2 - category model is soft-deletable', function () {
    // Given: a created category.
    $category = Category::factory()->create([
        'name' => 'Test Category',
    ]);

    // When: the category is deleted.
    $category->delete();

    // Then: the category is soft-deleted (not removed from DB).
    expect($category->trashed())->toBeTrue();

    // And: category is excluded from default queries.
    expect(Category::query()->find($category->id))->toBeNull();
});

/**
 * Epic: EIF-22_QA2 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-02_EIF-22_QA2 - soft-deleted category can be restored', function () {
    // Given: a soft-deleted category.
    $category = Category::factory()->create();
    $category->delete();

    // When: the category is restored.
    $category->restore();

    // Then: the category is no longer trashed.
    expect($category->trashed())->toBeFalse();

    // And: category is included in default queries.
    expect(Category::query()->find($category->id))->not->toBeNull();
});

/**
 * Epic: EIF-22_QA2 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-03_EIF-22_QA2 - category has many products relationship', function () {
    // Given: a category with multiple products.
    $category = Category::factory()->create();
    Product::factory()->count(3)->create(['category_id' => $category->id]);

    // When: accessing the products relationship.
    $products = $category->products;

    // Then: all related products are returned.
    expect($products)->toHaveCount(3);
    expect($products->every(fn ($p) => $p->category_id === $category->id))->toBeTrue();
});

/**
 * Epic: EIF-22_QA2 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-04_EIF-22_QA2 - category model is mass-assignable for fillable attributes', function () {
    // When: a category is created with mass assignment.
    $category = Category::create([
        'name' => 'Test Category',
        'description' => 'Test Description',
    ]);

    // Then: all attributes are persisted correctly.
    expect($category->name)->toBe('Test Category');
    expect($category->description)->toBe('Test Description');
});

/**
 * Epic: EIF-22_QA2 - Gestión de Recursos e Inventario
 * Priority: Low
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-05_EIF-22_QA2 - category timestamps are tracked', function () {
    // When: a category is created.
    $category = Category::factory()->create();
    $createdAt = $category->created_at;

    // Then: created_at timestamp exists.
    expect($createdAt)->not->toBeNull();

    // When: the category is updated.
    $category->update(['name' => 'Updated']);
    $updatedAt = $category->updated_at;

    // Then: updated_at is at or after created_at.
    expect($updatedAt->greaterThanOrEqualTo($createdAt))->toBeTrue();
});
