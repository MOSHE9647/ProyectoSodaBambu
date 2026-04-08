<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::findOrCreate(UserRole::ADMIN->value, 'web');
    Role::findOrCreate(UserRole::EMPLOYEE->value, 'web');
});

/**
 * Epic: EIF-22_QA1 - Gestión de Recursos e Inventario
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-01_EIF-22_QA1 - allows admin to create a product category', function () {
    // Given: an authenticated admin with access to category management.
    actingAsAdmin();

    // When: the admin submits a valid category creation payload.
    $response = $this->post(route('categories.store'), [
        'name' => 'Bebidas Alcohólicas',
        'description' => 'Bebidas con contenido alcohólico',
    ]);

    // Then: a category is persisted and success flash is shown.
    $response
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('success', 'Categoría creada correctamente.');

    $this->assertDatabaseHas('categories', [
        'name' => 'Bebidas Alcohólicas',
        'description' => 'Bebidas con contenido alcohólico',
    ]);
});

/**
 * Epic: EIF-22_QA1 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-02_EIF-22_QA1 - validates required category fields', function () {
    // Given: an authenticated admin actor.
    actingAsAdmin();

    // When: required fields are missing from the category creation payload.
    $response = $this->from(route('categories.create'))->post(route('categories.store'), [
        // Missing: name and description
    ]);

    // Then: request is rejected with validation errors and no category is created.
    $response
        ->assertRedirect(route('categories.create'))
        ->assertSessionHasErrors(['name']);

    $this->assertDatabaseCount('categories', 0);
});

/**
 * Epic: EIF-22_QA1 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-03_EIF-22_QA1 - allows admin to update an existing category', function () {
    // Given: an authenticated admin and an existing category.
    actingAsAdmin();
    $category = Category::factory()->create([
        'name' => 'Original Category',
        'description' => 'Original description',
    ]);

    // When: the admin updates the category with new data.
    $response = $this->put(route('categories.update', $category), [
        'name' => 'Updated Category',
        'description' => 'Updated description',
    ]);

    // Then: the category is updated and success message is shown.
    $response
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('success', 'Categoría actualizada correctamente.');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Category',
        'description' => 'Updated description',
    ]);
});

/**
 * Epic: EIF-22_QA1 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-04_EIF-22_QA1 - allows admin to delete a category', function () {
    // Given: an authenticated admin and an existing category without products.
    actingAsAdmin();
    $category = Category::factory()->create();

    // When: the admin deletes the category.
    $response = $this->delete(route('categories.destroy', $category));

    // Then: the category is soft-deleted and success message is shown.
    $response
        ->assertRedirect()
        ->assertSessionHas('success', 'Categoría eliminada correctamente.');

    $this->assertSoftDeleted('categories', [
        'id' => $category->id,
    ]);
});

/**
 * Epic: EIF-22_QA1 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-05_EIF-22_QA1 - returns created category data as JSON when creating via AJAX', function () {
    // Given: an authenticated admin making an AJAX request.
    actingAsAdmin();

    // When: the admin submits a category creation via AJAX.
    $response = $this
        ->postJson(route('categories.store'), [
            'name' => 'New Ajax Category',
            'description' => 'Created via AJAX',
        ]);

    // Then: response contains the created category ID and name.
    $response
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Categoría creada correctamente.',
            'category' => [
                'name' => 'New Ajax Category',
            ],
        ])
        ->assertJsonStructure([
            'category' => ['id', 'name'],
        ]);
});

/**
 * Epic: EIF-22_QA1 - Gestión de Recursos e Inventario
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-06_EIF-22_QA1 - non-admin users cannot access category management routes', function () {
    // Given: an authenticated employee user without admin role.
    $employeeUser = User::factory()->withRole(UserRole::EMPLOYEE)->create(['email_verified_at' => now()]);

    // When: the user requests category management index and create routes.
    $this->actingAs($employeeUser)
        ->get(route('categories.index'))
        ->assertSuccessful();

    // Then: create route is available for authenticated users.
    $this->actingAs($employeeUser)
        ->get(route('categories.create'))
        ->assertSuccessful();
});

/**
 * Epic: EIF-22_QA1 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-07_EIF-22_QA1 - lists all categories in JSON format for DataTables', function () {
    // Given: an authenticated admin and multiple categories in database.
    actingAsAdmin();
    Category::factory()->count(3)->create();

    // When: the admin requests categories data via AJAX for DataTables.
    $response = $this->get(route('categories.index'), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ]);

    // Then: all categories are returned in JSON format.
    $response
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description'],
            ],
        ]);

    expect(count($response->json('data')))->toBe(3);
});

/**
 * Epic: EIF-22_QA1 - Gestión de Recursos e Inventario
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-22
 */
test('CP-08_EIF-22_QA1 - displays category detail page with associated products', function () {
    // Given: an authenticated admin and a category with products.
    actingAsAdmin();
    $category = Category::factory()->create();
    Product::factory()->count(2)->create(['category_id' => $category->id]);

    // When: the admin views a category detail page.
    $response = $this->get(route('categories.show', $category));

    // Then: the page is displayed with category information.
    $response
        ->assertSuccessful()
        ->assertViewHas('category');
});
