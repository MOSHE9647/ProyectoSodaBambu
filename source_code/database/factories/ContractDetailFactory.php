<?php

namespace Database\Factories;

use App\Enums\MealTime;
use App\Models\Contract;
use App\Models\ContractDetail;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractDetail>
 */
class ContractDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'product_id' => Product::factory(),
            'meal_time' => $this->faker->randomElement(MealTime::values()),
            'serve_date' => $this->faker->dateTimeBetween('now', '+1 month')
                ->format('Y-m-d'),
        ];
    }
}
