<?php

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;

test('CP-01_EIF-33 - integrates product creation with sale price calculation', function () {
    // Dado que: existe un usuario administrador y una categoría para asignar el producto.
    $admin = User::factory()->withRole(UserRole::ADMIN)->create();
    $category = Category::factory()->create();

    // Cuando: el administrador registra un producto de mercadería desde la ruta de creación.
    $response = $this->actingAs($admin)->post(route('products.store'), [
        'category_id' => $category->id,
        'barcode' => '7501234567001',
        'name' => 'Producto de integración EIF-33',
        'type' => ProductType::MERCHANDISE->value,
        'has_inventory' => true,
        'current_stock' => 20,
        'minimum_stock' => 5,
        'reference_cost' => 1500,
        'tax_percentage' => 13,
        'margin_percentage' => 35,
        'expiration_date' => now()->addDays(30)->format('Y-m-d'),
        'expiration_alert_days' => 7,
    ]);

    // Entonces: el flujo completo redirige correctamente y persiste el producto calculado.
    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success', 'Producto creado exitosamente.');

    $product = Product::where('barcode', '7501234567001')->firstOrFail();

    expect($product->name)->toBe('Producto de integración EIF-33')
        ->and((float) $product->sale_price)->toBe(2288.0)
        ->and((float) $product->tax_percentage)->toBe(13.0)
        ->and((float) $product->margin_percentage)->toBe(35.0);

    $this->assertDatabaseHas('product_stocks', [
        'product_id' => $product->id,
        'current_stock' => 20,
        'minimum_stock' => 5,
    ]);

    expect(ProductStock::where('product_id', $product->id)->exists())->toBeTrue();
});
