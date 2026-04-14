<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleDetail>
 */
class SaleDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 50),
            'unit_price' => $this->faker->randomFloat(2, 25, 60000),
            'applied_tax' => $this->faker->randomFloat(2, 13, 20),
            'sub_total' => $this->faker->randomFloat(2, 1250, 3000000),
        ];
    }
}
