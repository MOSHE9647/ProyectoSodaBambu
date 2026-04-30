<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\WeekDay;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 months', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 year');

        return [
            'user_id' => User::factory()->withRole(UserRole::ADMIN), // Create a new user for each contract
            'client_id' => Client::factory(), // Create a new client for each contract
            'business_name' => $this->faker->company(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'days_to_serve' => json_encode($this->faker->randomElements(
                WeekDay::names(),
                rand(1, 7)
            )),
            'portions_per_day' => $this->faker->numberBetween(1, 100),
            'total_value' => $this->faker->randomFloat(0, 1000, 10000),
        ];
    }
}
