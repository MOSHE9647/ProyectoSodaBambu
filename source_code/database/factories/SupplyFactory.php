<?php

namespace Database\Factories;

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
            'measure_unit' => $this->faker->randomElement(['kg', 'litros', 'unidades']),
            'quantity' => $this->faker->numberBetween(0, 250),
            'unit_price' => $this->faker->randomFloat(2, 100, 20000),
            'expiration_date' => $this->faker->optional(0.85)->dateTimeBetween('today', '+10 months')?->format('Y-m-d'),
        ];
    }
}
