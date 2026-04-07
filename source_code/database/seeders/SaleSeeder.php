<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Employee;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing employees to assign sales to them
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->warn('No hay empleados en la base de datos. Por favor, corre EmployeeSeeder primero.');
            return;
        }

        // Generate sales for TODAY
        for ($i = 1; $i <= 5; $i++) {
            Sale::create([
                'employee_id' => $employees->random()->id,
                'invoice_number' => 'VNT-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'payment_status' => PaymentStatus::PAID,
                'date' => now()->subMinutes(rand(1, 480)), 
                'total' => rand(2500, 45000), 
            ]);
        }

        // Generate historical sales
        for ($i = 1; $i <= 15; $i++) {
            Sale::create([
                'employee_id' => $employees->random()->id,
                'invoice_number' => 'VNT-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'payment_status' => fake()->randomElement([PaymentStatus::PAID, PaymentStatus::PENDING]),
                'date' => now()->subDays(rand(1, 30)),
                'total' => rand(5000, 75000),
            ]);
        }
    }
}