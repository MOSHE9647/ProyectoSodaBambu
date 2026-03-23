<?php

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;

function createAdminUserForProduct(): User
{
    return User::factory()->withRole(UserRole::ADMIN)->create();
}

function createEmployeeUserForProduct(): User
{
    return User::factory()->withRole(UserRole::EMPLOYEE)->create();
}

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-01_EIF-32 - admin can create a valid category for later product assignment', function () {
    // Given: an authenticated admin user.
    $admin = createAdminUserForProduct();

    // When: the admin creates a category.
    $response = $this->actingAs($admin)->post(route('categories.store'), [
        'name' => 'Abarrotes',
        'description' => 'Categoria para productos de inventario',
    ]);

    // Then: category is stored successfully.
    $response
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('success', 'Categoría creada correctamente.');

    $this->assertDatabaseHas('categories', [
        'name' => 'Abarrotes',
    ]);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Highest
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-02_EIF-32 - creates merchandise product and auto-calculates sale price', function () {
    // Given: an authenticated admin and an existing category.
    $admin = createAdminUserForProduct();
    $category = Category::factory()->create();

    // When: the admin creates a merchandise product with inventory enabled.
    $response = $this->actingAs($admin)->post(route('products.store'), [
        'category_id' => $category->id,
        'barcode' => '7501234567890',
        'name' => 'Cafe en grano',
        'type' => ProductType::MERCHANDISE->value,
        'has_inventory' => true,
        'reference_cost' => 10000,
        'tax_percentage' => 13,
        'margin_percentage' => 35,
        'minimum_stock' => 8,
    ]);

    // Then: the system stores the product, computes sale_price, and creates stock row.
    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Producto creado exitosamente.');

    $product = Product::where('barcode', '7501234567890')->firstOrFail();

    // Expected: (10000 + 13%) + 35% = 15255.00
    expect((float) $product->sale_price)->toBe(15255.0);

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $product->id,
        'minimum_stock' => 8,
    ]);

    $stock = ProductStock::where('product_id', $product->id)->firstOrFail();
    expect($stock->current_stock)->toBeGreaterThanOrEqual(20)
        ->and($stock->current_stock)->toBeLessThanOrEqual(100);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-03_EIF-32 - creates dish product with manual sale price and no inventory row', function () {
    // Given: an authenticated admin and an existing category.
    $admin = createAdminUserForProduct();
    $category = Category::factory()->create();

    // When: the admin creates a dish product with manual sale price.
    $response = $this->actingAs($admin)->post(route('products.store'), [
        'category_id' => $category->id,
        'barcode' => '7501234567891',
        'name' => 'Casado completo',
        'type' => ProductType::DISH->value,
        'has_inventory' => false,
        'sale_price' => 4500,
    ]);

    // Then: product is stored with manual sale price and no stock record.
    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Producto creado exitosamente.');

    $product = Product::where('barcode', '7501234567891')->firstOrFail();

    expect((float) $product->sale_price)->toBe(4500.0)
        ->and((float) $product->reference_cost)->toBe(0.0)
        ->and((float) $product->tax_percentage)->toBe(0.0)
        ->and((float) $product->margin_percentage)->toBe(0.0);

    expect(ProductStock::where('product_id', $product->id)->exists())->toBeFalse();
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-04_EIF-32 - validates minimum stock when inventory is enabled', function () {
    // Given: an authenticated admin and an existing category.
    $admin = createAdminUserForProduct();
    $category = Category::factory()->create();

    // When: the admin submits product data with has_inventory=true but missing minimum_stock.
    $response = $this->actingAs($admin)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'category_id' => $category->id,
            'barcode' => '7501234567892',
            'name' => 'Azucar refinada',
            'type' => ProductType::MERCHANDISE->value,
            'has_inventory' => true,
            'reference_cost' => 1200,
            'tax_percentage' => 13,
            'margin_percentage' => 35,
        ]);

    // Then: validation rejects the request and no product is created.
    $response
        ->assertRedirect(route('products.create'))
        ->assertSessionHasErrors(['minimum_stock']);

    expect(Product::where('barcode', '7501234567892')->exists())->toBeFalse();
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-05_EIF-32 - updates existing product data successfully', function () {
    // Given: an authenticated admin with an existing product.
    $admin = createAdminUserForProduct();
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'barcode' => '7501234567893',
        'name' => 'Producto inicial',
        'type' => ProductType::MERCHANDISE->value,
        'has_inventory' => true,
        'reference_cost' => 1000,
        'tax_percentage' => 0.13,
        'margin_percentage' => 0.35,
        'sale_price' => 1525.5,
    ]);

    ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => 40,
        'minimum_stock' => 10,
    ]);

    // When: admin updates product fields.
    $response = $this->actingAs($admin)->put(route('products.update', $product), [
        'category_id' => $category->id,
        'barcode' => '7501234567893',
        'name' => 'Producto actualizado',
        'type' => ProductType::MERCHANDISE->value,
        'has_inventory' => true,
        'reference_cost' => 1500,
        'tax_percentage' => 13,
        'margin_percentage' => 40,
        'minimum_stock' => 12,
    ]);

    // Then: product and stock minimum values are updated.
    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Producto actualizado exitosamente.');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Producto actualizado',
    ]);

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $product->id,
        'minimum_stock' => 12,
    ]);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-06_EIF-32 - soft deletes product', function () {
    // Given: an authenticated admin with an existing product.
    $admin = createAdminUserForProduct();
    $product = Product::factory()->create();

    // When: admin deletes the product.
    $response = $this->actingAs($admin)->delete(route('products.destroy', $product));

    // Then: product is soft deleted and success feedback is provided.
    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Producto eliminado exitosamente.');

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-07_EIF-32 - employee can view products list but cannot access product creation actions', function () {
    // Given: an authenticated employee user.
    $employee = createEmployeeUserForProduct();

    // When/Then: employee is forbidden to access create or store product actions.
    $this->actingAs($employee)
        ->get(route('products.create'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->post(route('products.store'), [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Intento no permitido',
            'type' => ProductType::DISH->value,
            'has_inventory' => false,
            'sale_price' => 3000,
        ])
        ->assertForbidden();
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-08_EIF-32 - allows creating a category and using it in product registration', function () {
    // Given: an authenticated admin user.
    $admin = createAdminUserForProduct();

    // When: admin creates a new category then creates a product with that category.
    $categoryResponse = $this->actingAs($admin)->post(route('categories.store'), [
        'name' => 'Panaderia',
        'description' => 'Productos de panaderia',
    ]);

    $categoryResponse
        ->assertRedirect(route('categories.index'))
        ->assertSessionHas('success', 'Categoría creada correctamente.');

    $category = Category::where('name', 'Panaderia')->firstOrFail();

    $productResponse = $this->actingAs($admin)->post(route('products.store'), [
        'category_id' => $category->id,
        'barcode' => '7501234567894',
        'name' => 'Pan casero',
        'type' => ProductType::DISH->value,
        'has_inventory' => false,
        'sale_price' => 1800,
    ]);

    // Then: both category and product are persisted successfully.
    $productResponse
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Producto creado exitosamente.');

    $this->assertDatabaseHas('products', [
        'barcode' => '7501234567894',
        'category_id' => $category->id,
    ]);
});
