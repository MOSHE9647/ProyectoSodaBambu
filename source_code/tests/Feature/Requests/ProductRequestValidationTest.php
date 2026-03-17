<?php

namespace Tests\Feature\Requests;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRequestValidationTest extends TestCase
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
            'barcode' => '7791234599001',
            'name' => 'Producto valido',
            'type' => ProductType::MERCHANDISE->value,
            'has_inventory' => true,
            'reference_cost' => 1000,
            'tax_percentage' => 0.13,
            'margin_percentage' => 0.35,
            'sale_price' => 1525.50,
        ], $overrides);
    }

    public function test_store_requires_sale_price_for_dish_but_not_for_merchandise(): void
    {
        $this->actingAsAdmin();

        $dishMissingSalePrice = $this->from(route('products.create'))->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599002',
            'type' => ProductType::DISH->value,
            'sale_price' => '',
        ]));
        $dishMissingSalePrice->assertRedirect(route('products.create'));
        $dishMissingSalePrice->assertSessionHasErrors('sale_price');

        $merchandiseMissingSalePrice = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599003',
            'type' => ProductType::MERCHANDISE->value,
            'sale_price' => '',
        ]));
        $merchandiseMissingSalePrice->assertRedirect(route('products.index'));
    }

    public function test_store_rejects_negative_reference_cost_and_invalid_type(): void
    {
        $this->actingAsAdmin();

        $negativeCost = $this->from(route('products.create'))->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599004',
            'reference_cost' => -1,
        ]));
        $negativeCost->assertRedirect(route('products.create'));
        $negativeCost->assertSessionHasErrors('reference_cost');

        $invalidType = $this->from(route('products.create'))->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599005',
            'type' => 'combo',
        ]));
        $invalidType->assertRedirect(route('products.create'));
        $invalidType->assertSessionHasErrors('type');
    }

    public function test_store_normalizes_integer_percentages_to_decimal_values(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599006',
            'tax_percentage' => 13,
            'margin_percentage' => 35,
            'sale_price' => '',
        ]));

        $response->assertRedirect(route('products.index'));

        $product = Product::where('barcode', '7791234599006')->first();

        $this->assertNotNull($product);
        $this->assertSame('0.13', $product->tax_percentage);
        $this->assertSame('0.35', $product->margin_percentage);
    }

    public function test_store_validates_sale_price_greater_than_reference_cost_for_dish(): void
    {
        $this->actingAsAdmin();

        $salePriceLessThanCost = $this->from(route('products.create'))->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599007',
            'type' => ProductType::DISH->value,
            'reference_cost' => 1000,
            'sale_price' => 500,
        ]));
        $salePriceLessThanCost->assertRedirect(route('products.create'));
        $salePriceLessThanCost->assertSessionHasErrors('sale_price');

        $salePriceGreaterThanCost = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599008',
            'type' => ProductType::DISH->value,
            'reference_cost' => 1000,
            'sale_price' => 2000,
        ]));
        $salePriceGreaterThanCost->assertRedirect(route('products.index'));
    }

    public function test_store_rejects_decimals_exceeding_two_precision(): void
    {
        $this->actingAsAdmin();

        $costWith3Decimals = $this->from(route('products.create'))->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599009',
            'reference_cost' => 1000.125,
        ]));
        $costWith3Decimals->assertRedirect(route('products.create'));
        $costWith3Decimals->assertSessionHasErrors('reference_cost');

        $priceWith3Decimals = $this->from(route('products.create'))->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599010',
            'type' => ProductType::DISH->value,
            'sale_price' => 3000.999,
        ]));
        $priceWith3Decimals->assertRedirect(route('products.create'));
        $priceWith3Decimals->assertSessionHasErrors('sale_price');

        $validPriceTwoDecimals = $this->post(route('products.store'), $this->validPayload([
            'barcode' => '7791234599011',
            'type' => ProductType::DISH->value,
            'reference_cost' => 1000.50,
            'sale_price' => 2000.75,
        ]));
        $validPriceTwoDecimals->assertRedirect(route('products.index'));
    }
}
