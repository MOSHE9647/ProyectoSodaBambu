<?php

namespace Database\Factories;

use App\Enums\MeasureUnit;
use App\Models\Supply;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supply>
 */
class SupplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'brand' => $this->faker->company(),
            'quantity' => $this->faker->numberBetween(0, 250), // Random quantity between 0 and 250
            'measure_unit' => $this->faker->randomElement(MeasureUnit::cases()),
            'measure_amount' => $this->faker->randomFloat(2, 0.5, 50),
            'unit_price' => $this->faker->numberBetween(1, 400) * 5, // Random cost between 5 and 2000
            'expiration_date' => $this->faker->optional(0.65)->dateTimeBetween('today', '+8 months')?->format('Y-m-d'),
            'expiration_alert_days' => $this->faker->numberBetween(3, 10),
        ];
    }
}
