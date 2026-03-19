<?php

namespace Tests\Feature\Controllers;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::ADMIN)->create());
    }

    private function actingAsEmployee(): void
    {
        $this->actingAs(User::factory()->withRole(UserRole::EMPLOYEE)->create());
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'category_id' => Category::factory()->create()->id,
            'barcode' => '7791234500001',
            'name' => 'Producto de prueba',
            'type' => ProductType::MERCHANDISE->value,
            'has_inventory' => true,
            'reference_cost' => 1000,
            'tax_percentage' => 0.13,
            'margin_percentage' => 0.35,
            'sale_price' => 0,
            'current_stock' => 20,
            'minimum_stock' => 10,
        ], $overrides);
    }

    public function test_store_calculates_sale_price_automatically_for_merchandise(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234500002',
            'reference_cost' => 1000,
            'tax_percentage' => 13,
            'margin_percentage' => 35,
            'sale_price' => 1,
        ]));

        $response->assertRedirect(route('products.index'));

        $product = Product::where('barcode', '7791234500002')->first();

        $this->assertNotNull($product);
        $this->assertSame('0.13', $product->tax_percentage);
        $this->assertSame('0.35', $product->margin_percentage);
        $this->assertSame('1525.50', $product->sale_price);

        $stock = ProductStock::where('product_id', $product->id)->first();
        $this->assertNotNull($stock);
        $this->assertGreaterThanOrEqual(20, $stock->current_stock);
        $this->assertLessThanOrEqual(100, $stock->current_stock);
        $this->assertSame(10, $stock->minimum_stock);
    }

    public function test_store_uses_default_margin_for_merchandise_when_not_provided(): void
    {
        $this->actingAsAdmin();

        $payload = $this->validPayload([
            'barcode' => '7791234500003',
            'margin_percentage' => '',
            'reference_cost' => 1000,
            'tax_percentage' => 0.13,
        ]);

        $response = $this->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('barcode', '7791234500003')->first();

        $this->assertNotNull($product);
        $this->assertSame('0.35', $product->margin_percentage);
        $this->assertSame('1525.50', $product->sale_price);
    }

    public function test_store_allows_optional_sale_price_for_dish(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234500004',
            'type' => ProductType::DISH->value,
            'sale_price' => '',
            'reference_cost' => '',
            'tax_percentage' => '',
            'margin_percentage' => '',
        ]));

        $response->assertRedirect(route('products.index'));

        $product = Product::where('barcode', '7791234500004')->first();

        $this->assertNotNull($product);
        $this->assertSame('0.00', $product->sale_price);
        $this->assertSame('0.00', $product->reference_cost);
        $this->assertSame('0.00', $product->tax_percentage);
        $this->assertSame('0.00', $product->margin_percentage);
    }

    public function test_store_forces_zero_sale_price_for_packaged(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234500006',
            'type' => ProductType::PACKAGED->value,
            'reference_cost' => '',
            'tax_percentage' => '',
            'margin_percentage' => '',
            'sale_price' => 2750.50,
        ]));

        $response->assertRedirect(route('products.index'));

        $product = Product::where('barcode', '7791234500006')->first();

        $this->assertNotNull($product);
        $this->assertSame('0.00', $product->sale_price);
        $this->assertSame('0.00', $product->reference_cost);
        $this->assertSame('0.00', $product->tax_percentage);
        $this->assertSame('0.00', $product->margin_percentage);
    }

    public function test_update_recalculates_sale_price_for_merchandise(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'barcode' => '7791234500005',
            'type' => ProductType::MERCHANDISE->value,
            'reference_cost' => 1000,
            'tax_percentage' => 0.13,
            'margin_percentage' => 0.35,
            'sale_price' => 1500,
        ]);

        $response = $this->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'type' => ProductType::MERCHANDISE->value,
            'has_inventory' => true,
            'reference_cost' => 2000,
            'tax_percentage' => 0.13,
            'margin_percentage' => 0.35,
            'sale_price' => 1,
            'minimum_stock' => 15,
        ]);

        $response->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertSame('3051.00', $product->sale_price);
    }

    public function test_store_does_not_try_to_restore_soft_deleted_product_when_barcode_is_empty(): void
    {
        $this->actingAsAdmin();

        $deletedProduct = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'barcode' => null,
        ]);
        $deletedProduct->delete();

        $response = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '',
            'sale_price' => '',
        ]));

        $response->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $deletedProduct->id]);
        $this->assertSame(2, Product::withTrashed()->count());
    }

    public function test_store_does_not_require_stock_fields_when_has_inventory_is_false(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234500007',
            'has_inventory' => false,
            'current_stock' => '',
            'minimum_stock' => '',
        ]));

        $response->assertRedirect(route('products.index'));

        $product = Product::where('barcode', '7791234500007')->first();

        $this->assertNotNull($product);
        $this->assertFalse($product->has_inventory);
        $this->assertDatabaseMissing('product_stocks', ['product_id' => $product->id]);
    }

    public function test_update_syncs_product_stock_when_has_inventory_is_true(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'barcode' => '7791234500010',
            'type' => ProductType::MERCHANDISE->value,
            'has_inventory' => true,
            'reference_cost' => 1000,
            'tax_percentage' => 0.13,
            'margin_percentage' => 0.35,
            'sale_price' => 1525.50,
        ]);

        ProductStock::factory()->create([
            'product_id' => $product->id,
            'current_stock' => 12,
            'minimum_stock' => 5,
        ]);

        $response = $this->put(route('products.update', $product), [
            'category_id' => $product->category_id,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'type' => ProductType::MERCHANDISE->value,
            'has_inventory' => true,
            'reference_cost' => 2000,
            'tax_percentage' => 0.13,
            'margin_percentage' => 0.35,
            'sale_price' => 1,
            'current_stock' => 30,
            'minimum_stock' => 8,
        ]);

        $response->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertSame('3051.00', $product->sale_price);
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $product->id,
            'current_stock' => 12,
            'minimum_stock' => 8,
        ]);
    }

    public function test_edit_prefills_stock_fields_from_relational_inventory_table(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'barcode' => '7791234500011',
            'type' => ProductType::MERCHANDISE->value,
            'has_inventory' => true,
            'reference_cost' => 1000,
            'tax_percentage' => 0.13,
            'margin_percentage' => 0.35,
            'sale_price' => 1525.50,
        ]);

        ProductStock::factory()->create([
            'product_id' => $product->id,
            'current_stock' => 42,
            'minimum_stock' => 11,
        ]);

        $response = $this->get(route('products.edit', $product));

        $response->assertOk();
        $response->assertSee('name="current_stock"', false);
        $response->assertSee('value="42"', false);
        $response->assertSee('name="minimum_stock"', false);
        $response->assertSee('value="11"', false);
    }

    public function test_index_shows_low_stock_alert_when_products_are_below_minimum(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        $lowStockProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Refresco Cola',
            'has_inventory' => true,
        ]);

        ProductStock::factory()->create([
            'product_id' => $lowStockProduct->id,
            'current_stock' => 4,
            'minimum_stock' => 10,
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Alerta de stock bajo', false);
        $response->assertSee('Refresco Cola', false);
    }

    public function test_index_ajax_filters_only_low_stock_products_when_low_stock_flag_is_enabled(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        $lowStockProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Jugo Naranja',
            'has_inventory' => true,
        ]);

        ProductStock::factory()->create([
            'product_id' => $lowStockProduct->id,
            'current_stock' => 3,
            'minimum_stock' => 10,
        ]);

        $healthyStockProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Gaseosa Lima',
            'has_inventory' => true,
        ]);

        ProductStock::factory()->create([
            'product_id' => $healthyStockProduct->id,
            'current_stock' => 30,
            'minimum_stock' => 10,
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get(route('products.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'low_stock' => 1,
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Jugo Naranja']);
        $response->assertJsonMissing(['name' => 'Gaseosa Lima']);
    }

    public function test_show_includes_current_and_minimum_stock_information(): void
    {
        $this->actingAsAdmin();

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'name' => 'Cafe Molido',
            'has_inventory' => true,
        ]);

        ProductStock::factory()->create([
            'product_id' => $product->id,
            'current_stock' => 18,
            'minimum_stock' => 7,
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Stock Actual', false);
        $response->assertSee('value="18"', false);
        $response->assertSee('Stock Mínimo', false);
        $response->assertSee('value="7"', false);
    }

    public function test_index_ajax_allows_search_using_current_stock_column(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        $targetProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Producto Target Stock Actual',
            'has_inventory' => true,
        ]);

        ProductStock::factory()->create([
            'product_id' => $targetProduct->id,
            'current_stock' => 73,
            'minimum_stock' => 10,
        ]);

        ProductStock::factory()->create([
            'product_id' => Product::factory()->create([
                'category_id' => $category->id,
                'name' => 'Producto Secundario Stock Actual',
                'has_inventory' => true,
            ])->id,
            'current_stock' => 22,
            'minimum_stock' => 10,
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get(route('products.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'columns' => [
                ['data' => 'barcode', 'name' => 'barcode', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'name', 'name' => 'name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'category', 'name' => 'category_id', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'type', 'name' => 'type', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'current_stock', 'name' => 'ps.current_stock', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '73', 'regex' => 'false']],
                ['data' => 'minimum_stock', 'name' => 'ps.minimum_stock', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'sale_price', 'name' => 'sale_price', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Producto Target Stock Actual']);
        $response->assertJsonMissing(['name' => 'Producto Secundario Stock Actual']);
    }

    public function test_index_ajax_allows_search_using_minimum_stock_column(): void
    {
        $this->actingAsAdmin();

        $category = Category::factory()->create();

        $targetProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Producto Target Stock Minimo',
            'has_inventory' => true,
        ]);

        ProductStock::factory()->create([
            'product_id' => $targetProduct->id,
            'current_stock' => 45,
            'minimum_stock' => 17,
        ]);

        ProductStock::factory()->create([
            'product_id' => Product::factory()->create([
                'category_id' => $category->id,
                'name' => 'Producto Secundario Stock Minimo',
                'has_inventory' => true,
            ])->id,
            'current_stock' => 45,
            'minimum_stock' => 8,
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get(route('products.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'columns' => [
                ['data' => 'barcode', 'name' => 'barcode', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'name', 'name' => 'name', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'category', 'name' => 'category_id', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'type', 'name' => 'type', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'current_stock', 'name' => 'ps.current_stock', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
                ['data' => 'minimum_stock', 'name' => 'ps.minimum_stock', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '17', 'regex' => 'false']],
                ['data' => 'sale_price', 'name' => 'sale_price', 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
            ],
            'search' => ['value' => '', 'regex' => 'false'],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]));

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Producto Target Stock Minimo']);
        $response->assertJsonMissing(['name' => 'Producto Secundario Stock Minimo']);
    }

    public function test_employee_can_only_view_products(): void
    {
        $this->actingAsEmployee();

        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
        ]);

        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.show', $product))->assertOk();
        $this->get(route('products.create'))->assertForbidden();
        $this->get(route('products.edit', $product))->assertForbidden();
    }

    public function test_employee_cannot_store_update_or_delete_products(): void
    {
        $this->actingAsEmployee();

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->post(route('products.store'), [
            'category_id' => $category->id,
            'barcode' => '9000000000011',
            'name' => 'Producto bloqueado',
            'type' => ProductType::DISH->value,
            'has_inventory' => true,
            'reference_cost' => '',
            'tax_percentage' => '',
            'margin_percentage' => '',
            'sale_price' => '',
            'minimum_stock' => 10,
        ])->assertForbidden();

        $this->put(route('products.update', $product), [
            'category_id' => $category->id,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'type' => ProductType::DISH->value,
            'has_inventory' => true,
            'reference_cost' => '',
            'tax_percentage' => '',
            'margin_percentage' => '',
            'sale_price' => '',
            'minimum_stock' => 10,
        ])->assertForbidden();

        $this->delete(route('products.destroy', $product))->assertForbidden();
    }
}
