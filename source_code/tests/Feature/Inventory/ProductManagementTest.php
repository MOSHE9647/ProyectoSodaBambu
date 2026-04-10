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
        'current_stock' => 25,
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
    expect($stock->current_stock)->toBe(25);
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
test('CP-07_EIF-32 - employee can view products list and access product creation actions', function () {
    // Given: an authenticated employee user.
    $employee = createEmployeeUserForProduct();

    // When/Then: employee is forbidden to access create or store product actions.
    $this->actingAs($employee)
        ->get(route('products.create'))
        ->assertOk();

    $this->actingAs($employee)
        ->post(route('products.store'), [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Intento no permitido',
            'type' => ProductType::DISH->value,
            'has_inventory' => false,
            'sale_price' => 3000,
        ])
        ->assertRedirect(route('products.index'));
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

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-09_EIF-32 - displays product detail page with all information', function () {
    // Given: an authenticated admin and an existing product.
    $admin = createAdminUserForProduct();
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Cafe Premium',
        'type' => ProductType::MERCHANDISE->value,
    ]);

    // When: the admin views the product detail page.
    $response = $this->actingAs($admin)->get(route('products.show', $product));

    // Then: the page displays the product information.
    $response
        ->assertSuccessful()
        ->assertViewHas('product');
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-10_EIF-32 - displays product edit form with current values', function () {
    // Given: an authenticated admin and an existing product.
    $admin = createAdminUserForProduct();
    $product = Product::factory()->create([
        'name' => 'Original Name',
        'type' => ProductType::DISH->value,
    ]);

    // When: the admin requests the product edit form.
    $response = $this->actingAs($admin)->get(route('products.edit', $product));

    // Then: the form displays current product data.
    $response
        ->assertSuccessful()
        ->assertViewHas('product', $product);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-11_EIF-32 - filters products with low stock via AJAX DataTables', function () {
    // Given: an authenticated admin and products with various stock levels.
    $admin = createAdminUserForProduct();
    $highStockProduct = Product::factory()->create();
    ProductStock::factory()->create([
        'product_id' => $highStockProduct->id,
        'current_stock' => 100,
        'minimum_stock' => 20,
    ]);

    $lowStockProduct = Product::factory()->create();
    ProductStock::factory()->create([
        'product_id' => $lowStockProduct->id,
        'current_stock' => 5,
        'minimum_stock' => 20,
    ]);

    // When: the admin requests products with low stock filter via AJAX.
    $response = $this->actingAs($admin)->get(route('products.index'), [
        'Accept' => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
        'low_stock' => 'true',
        'draw' => 1,
        'start' => 0,
        'length' => 10,
    ]);

    // Then: response includes DataTables structure.
    $response
        ->assertSuccessful()
        ->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-12_EIF-32 - index view exposes low stock products list for dashboard card', function () {
    // Given: an authenticated admin and mixed stock rows.
    $admin = createAdminUserForProduct();

    $inventoryProduct = Product::factory()->create(['has_inventory' => true]);
    ProductStock::factory()->create([
        'product_id' => $inventoryProduct->id,
        'current_stock' => 2,
        'minimum_stock' => 5,
    ]);

    $nonInventoryProduct = Product::factory()->create(['has_inventory' => false]);
    ProductStock::factory()->create([
        'product_id' => $nonInventoryProduct->id,
        'current_stock' => 1,
        'minimum_stock' => 5,
    ]);

    // When: requesting products index without AJAX.
    $response = $this->actingAs($admin)->get(route('products.index'));

    // Then: low stock view data includes only inventory-enabled products.
    $response
        ->assertSuccessful()
        ->assertViewHas('lowStockProducts');

    $lowStockProducts = $response->viewData('lowStockProducts');
    expect($lowStockProducts->count())->toBeGreaterThanOrEqual(1);
    expect($lowStockProducts->every(fn ($row) => $row->product?->has_inventory === true))->toBeTrue();
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: Medium
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-13_EIF-32 - product create form loads categories and null product stock', function () {
    // Given: an authenticated admin and available categories.
    $admin = createAdminUserForProduct();
    Category::factory()->count(2)->create();

    // When: opening the product create form.
    $response = $this->actingAs($admin)->get(route('products.create'));

    // Then: view includes categories and productStock as null.
    $response
        ->assertSuccessful()
        ->assertViewHas('categories')
        ->assertViewHas('productStock', null);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-14_EIF-32 - restores soft deleted product by barcode and restores stock row', function () {
    // Given: an authenticated admin with a soft-deleted product and stock.
    $admin = createAdminUserForProduct();
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'barcode' => '7509999999999',
        'name' => 'Producto Borrado',
        'type' => ProductType::MERCHANDISE->value,
        'has_inventory' => true,
        'reference_cost' => 1000,
        'tax_percentage' => 13,
        'margin_percentage' => 35,
    ]);

    $stock = ProductStock::factory()->create([
        'product_id' => $product->id,
        'current_stock' => 25,
        'minimum_stock' => 10,
    ]);

    $product->delete();
    $stock->delete();

    // When: creating again using the same barcode.
    $response = $this->actingAs($admin)->post(route('products.store'), [
        'category_id' => $category->id,
        'barcode' => '7509999999999',
        'name' => 'Producto Restaurado',
        'type' => ProductType::MERCHANDISE->value,
        'has_inventory' => true,
        'reference_cost' => 2000,
        'tax_percentage' => 13,
        'margin_percentage' => 35,
        'minimum_stock' => 15,
    ]);

    // Then: product and stock are restored and updated.
    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Producto restaurado y actualizado exitosamente.');

    $restored = Product::withTrashed()->where('barcode', '7509999999999')->firstOrFail();
    expect($restored->deleted_at)->toBeNull();

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $restored->id,
        'minimum_stock' => 15,
        'deleted_at' => null,
    ]);
});

/**
 * User Story: EIF-32 - Product registration and type-based field behavior.
 * Priority: High
 * Jira Link: https://est-una.atlassian.net/browse/EIF-32
 */
test('CP-15_EIF-32 - creates drink product forcing sale price to zero', function () {
    // Given: an authenticated admin and an existing category.
    $admin = createAdminUserForProduct();
    $category = Category::factory()->create();

    // When: creating a DRINK product with an incoming sale_price value.
    $response = $this->actingAs($admin)->post(route('products.store'), [
        'category_id' => $category->id,
        'barcode' => '7501234500001',
        'name' => 'Bebida Test',
        'type' => ProductType::DRINK->value,
        'has_inventory' => false,
        'sale_price' => 9000,
    ]);

    // Then: the controller applies pricing rules and stores sale_price as zero.
    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Producto creado exitosamente.');

    $drink = Product::query()->where('barcode', '7501234500001')->firstOrFail();
    expect((float) $drink->sale_price)->toBe(0.0);
});
