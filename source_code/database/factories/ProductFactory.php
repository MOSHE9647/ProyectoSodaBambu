<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'barcode' => $this->faker->ean13(),
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement(ProductType::cases()),
            'has_inventory' => $this->faker->boolean(),
            'sale_price' => $this->faker->randomFloat(2, 1, 200),
            'tax_percentage' => $this->faker->randomFloat(2, 0, 25),
            'reference_cost' => $this->faker->randomFloat(2, 1, 100),
            'margin_percentage' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
