<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
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
        $userRole = $this->faker->randomElement([UserRole::ADMIN, UserRole::EMPLOYEE]);
        $paymentStatuses = [PaymentStatus::PAID, PaymentStatus::PENDING, PaymentStatus::CANCELLED, PaymentStatus::PARTIAL];

        return [
            'user_id' => User::factory()->withRole($userRole),
            'supplier_id' => Supplier::factory(),
            'invoice_number' => $this->faker->unique()->numerify('FAC-##########'),
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'total' => $this->faker->randomFloat(2, 20000, 600000),
            'payment_status' => $this->faker->randomElement($paymentStatuses),
        ];
    }
}
