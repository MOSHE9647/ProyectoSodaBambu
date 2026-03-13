<?php

namespace Tests\Feature\Controllers;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
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

    public function test_store_keeps_manual_sale_price_for_dish(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234500004',
            'type' => ProductType::DISH->value,
            'sale_price' => 3999.99,
            'reference_cost' => 1000,
            'tax_percentage' => 0.13,
            'margin_percentage' => 0.35,
        ]));

        $response->assertRedirect(route('products.index'));

        $product = Product::where('barcode', '7791234500004')->first();

        $this->assertNotNull($product);
        $this->assertSame('3999.99', $product->sale_price);
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
        ]);

        $response->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertSame('3051.00', $product->sale_price);
    }
}
