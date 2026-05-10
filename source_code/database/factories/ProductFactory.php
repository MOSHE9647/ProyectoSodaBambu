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
        $referenceCost = $this->faker->numberBetween(1, 400) * 5; // Random cost between 5 and 2000
        $marginPercentage = $this->faker->numberBetween(1, 35); // Random margin between 1% and 35%
        $taxPercentage = 13; // 13% tax

        return [
            'category_id' => Category::factory(),
            'barcode' => $this->faker->ean13(),
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement(ProductType::cases()),
            'expiration_date' => $this->faker->optional(0.65)->dateTimeBetween('today', '+8 months')?->format('Y-m-d'),
            'expiration_alert_days' => $this->faker->numberBetween(3, 10),
            'has_inventory' => $this->faker->boolean(),
            'sale_price' => Product::calculateSalePrice($referenceCost, $marginPercentage, $taxPercentage),
            'tax_percentage' => $taxPercentage,
            'reference_cost' => $referenceCost,
            'margin_percentage' => $marginPercentage,
        ];
    }
}