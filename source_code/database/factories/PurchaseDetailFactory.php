<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseDetail>
 */
class PurchaseDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::factory(),
            'purchasable_id' => $this->faker->randomElement([
                Product::factory(),
                Supply::factory(),
            ]),
            'purchasable_type' => $this->faker->randomElement([
                Product::class,
                Supply::class,
            ]),
            'subtotal' => $this->faker->randomFloat(2, 10, 25000),
        ];
    }
}
