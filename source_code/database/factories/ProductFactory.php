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
        $referenceCost = $this->faker->randomFloat(2, 500, 12000);
        $taxPercentage = $this->faker->randomElement([0.00, 0.01, 0.04, 0.08, 0.13]);
        $marginPercentage = $this->faker->randomFloat(2, 0.20, 0.60);
        $basePrice = $referenceCost + ($referenceCost * $taxPercentage);

        return [
            'category_id' => Category::factory(),
            'barcode' => $this->faker->ean13(),
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement(ProductType::cases()),
            'expiration_date' => $this->faker->optional(0.65)->dateTimeBetween('today', '+8 months')?->format('Y-m-d'),
            'has_inventory' => $this->faker->boolean(),
            'sale_price' => round($basePrice + ($basePrice * $marginPercentage), 2),
            'tax_percentage' => $taxPercentage,
            'reference_cost' => $referenceCost,
            'margin_percentage' => $marginPercentage,
        ];
    }
}
