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
        $quantity = $this->faker->numberBetween(1, 100);
        $unitPrice = $this->faker->randomFloat(2, 10, 1000);

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
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => round($quantity * $unitPrice, 2),
            'expiration_date' => $this->faker->optional(0.8)->dateTimeBetween('today', '+9 months')?->format('Y-m-d'),
        ];
    }
}
