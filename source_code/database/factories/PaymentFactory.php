<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originType = $this->faker->randomElement([
            Sale::class,
            Purchase::class,
            Contract::class,
            // Payroll::class
        ]);

        $originId = match ($originType) {
            Sale::class => Sale::factory()->create()->id,
            Purchase::class => Purchase::factory()->create()->id,
            Contract::class => Contract::factory()->create()->id,
            // Payroll::class => Payroll::factory()->create()->id,
            default => throw new \InvalidArgumentException("Tipo de origen de pago no soportado: {$originType}")
        };

        return [
            'amount' => 1000,
            'method' => PaymentMethod::CASH,
            'change_amount' => 0,
            'date' => now(),
            'origin_id' => $originId,
            'origin_type' => $originType,
        ];
    }
}
