<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\PaymentFrequency;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => User::factory()->withRole(), // Create a new user and use its ID
            'phone' => $this->faker->phoneNumber(),
            'status' => $this->faker->randomElement(EmployeeStatus::cases()),
            'payment_frequency' => $this->faker->randomElement(PaymentFrequency::cases()),
            'hourly_wage' => $this->faker->randomFloat(2, 1600, 2300),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
