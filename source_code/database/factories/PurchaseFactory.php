<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'invoice_number' => $this->faker->unique()->numerify('INV-#####'),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'total' => $this->faker->randomFloat(2, 10, 1000),
            'payment_status' => $this->faker->randomElement(PaymentStatus::cases()),
        ];
    }
}
