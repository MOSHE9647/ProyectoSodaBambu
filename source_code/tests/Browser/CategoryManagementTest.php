<?php

use App\Models\User;
use App\Enums\UserRole;

test('CP-01_EIF-00 - An admin user can log in and successfully create a category', function () {
    // Given: An admin user exists and is logged into the system.
    $adminPassword = 'admin123';
    $adminUser = User::factory()->withRole(UserRole::ADMIN)->create([
        'email' => 'admin@elbambu.com',
        'password' => $adminPassword,
    ]);

    $page = loginAsUser($adminUser, $adminPassword);
    $this->assertAuthenticatedAs($adminUser);

    // When: The admin navigates to the categories management section.
    $page->navigate(route('categories.index'));
    $page->assertSee('Gestión de Categorías');

    // And: The admin opens the creation form.
    $page->click('.create-button');
    $page->assertSee('Crear Categoría');

    // And: The admin fills in the category details and submits the form.
    $categoryData = [
        'name' => 'Bebidas Naturales ' . fake()->uuid(), // Unique to avoid conflicts
        'description' => 'Refrescos hechos con frutas de temporada.',
    ];

    $page->fill('#name', $categoryData['name'])
         ->fill('#description', $categoryData['description'])
         ->click('#create-category-form-button');

    // Then: The category is successfully created and displayed in the list.
    $page->assertSee('Categoría creada correctamente.');

    $page->fill('#customSearchBox', $categoryData['name'])
         ->waitForText($categoryData['name'])
         ->assertSee($categoryData['name']);

    // And: The category exists in the database.
    $this->assertDatabaseHas('categories', [
        'name' => $categoryData['name'],
        'description' => $categoryData['description'],
    ]);

    // And: The user can log out successfully.
    $page->script("document.getElementById('logout-form').submit();");

    $page->assertPathIs('/login')
         ->assertSee('Iniciar Sesión');

    $this->assertGuest();
});