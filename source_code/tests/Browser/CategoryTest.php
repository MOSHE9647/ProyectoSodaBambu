<?php

use App\Enums\UserRole;
use App\Models\User;

test('CP-FUN-05 - an administrator can create a category from the browser and return to the listing', function () {
    // Given: an administrator user exists in the database.
    $adminPassword = 'adminpassword';
    $adminUser = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'categories-admin@example.com',
        'password' => $adminPassword,
    ]);

    $categoryName = 'Categoría Navegación';
    $categoryDescription = 'Categoría creada desde el navegador';

    // When: the administrator user logs in through the browser helper.
    $page = loginAsUser($adminUser, $adminPassword);

    // And: the administrator opens the categories module.
    $page->navigate(route('categories.index'));

    // Then: the categories listing loads successfully.
    $page->assertSee('Gestión de Categorías')
        ->wait(5)
        ->click('.create-button')
        ->assertSee('Crear Categoría');

    // And: the administrator completes the category form and submits it.
    $page->fill('#category_name', $categoryName)
        ->fill('#description', $categoryDescription);

    $page->script('document.getElementById("create-category-form").submit()');

    // Then: the application returns to the listing and shows the new category.
    $page->assertSee('Categoría creada correctamente.')
        ->assertSee('Gestión de Categorías')
        ->wait(10)
        ->assertSee($categoryName);
})->group('s8-tests-melanie');
