<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userRole = $this->faker->randomElement([UserRole::ADMIN, UserRole::EMPLOYEE]);
        $paymentStatuses = [PaymentStatus::PAID, PaymentStatus::PENDING, PaymentStatus::CANCELLED, PaymentStatus::PARTIAL];

        return [
            'user_id' => User::factory()->withRole($userRole),
            'invoice_number' => $this->faker->unique()->numerify('INV-######'),
            'payment_status' => $this->faker->randomElement($paymentStatuses),
            'date' => $this->faker->dateTimeThisMonth(),
            'total' => $this->faker->randomFloat(2, 20000, 600000),
        ];
    }
}
