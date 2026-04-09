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
        $purchasableType = $this->faker->randomElement([Product::class, Supply::class]);
        $quantity = $this->faker->numberBetween(1, 50);
        $unitPrice = $this->faker->randomFloat(2, 100, 10000);

        return [
            'purchase_id' => Purchase::factory(),
            'purchasable_type' => $purchasableType,
            'purchasable_id' => $purchasableType === Product::class
                                    ? Product::factory()
                                    : Supply::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
            'expiration_date' => $this->faker->optional(0.6)->dateTimeBetween('+1 month', '+2 years'),
        ];
    }
}
