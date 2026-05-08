<?php

namespace Database\Factories;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashRegister>
 */
class CashRegisterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create()->id,
            'status' => CashRegisterStatus::OPEN->value,
            'opened_at' => now(),
            'closed_at' => null,
            'opening_balance' => 0.00,
            'closing_balance' => 0.00,
        ];
    }
}
