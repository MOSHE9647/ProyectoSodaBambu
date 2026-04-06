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
        // Obtenemos los empleados existentes (asegúrate de haber corrido EmployeeSeeder antes)
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->warn('No hay empleados en la base de datos. Por favor, corre EmployeeSeeder primero.');
            return;
        }

        // 1. Generar ventas para el día de HOY (para probar el dashboard)
        for ($i = 1; $i <= 5; $i++) {
            Sale::create([
                'employee_id' => $employees->random()->id,
                'invoice_number' => 'VNT-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'payment_status' => PaymentStatus::PAID,
                'date' => now()->subMinutes(rand(1, 480)), // Ventas repartidas en las últimas 8 horas
                'total' => rand(2500, 45000), // Montos realistas para una soda
            ]);
        }

        // 2. Generar ventas históricas (últimos 30 días)
        for ($i = 1; $i <= 15; $i++) {
            Sale::create([
                'employee_id' => $employees->random()->id,
                'invoice_number' => 'VNT-HIST-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'payment_status' => fake()->randomElement([PaymentStatus::PAID, PaymentStatus::PENDING]),
                'date' => now()->subDays(rand(1, 30)),
                'total' => rand(5000, 75000),
            ]);
        }
    }
}