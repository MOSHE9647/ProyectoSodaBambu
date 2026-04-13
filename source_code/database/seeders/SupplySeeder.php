<?php

namespace Database\Seeders;

use App\Models\Supply;
use Illuminate\Database\Seeder;

class SupplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supplies = [
            ['name' => 'Arroz Blanco', 'measure_unit' => 'Saco', 'quantity' => 18, 'unit_price' => 25500, 'expiration_date' => now()->addMonths(8)->toDateString(), 'expiration_alert_days' => 30],
            ['name' => 'Frijoles Negros', 'measure_unit' => 'Saco', 'quantity' => 14, 'unit_price' => 23800, 'expiration_date' => now()->addMonths(7)->toDateString(), 'expiration_alert_days' => 30],
            ['name' => 'Aceite Vegetal', 'measure_unit' => 'Pichinga', 'quantity' => 10, 'unit_price' => 19850, 'expiration_date' => now()->addMonths(5)->toDateString(), 'expiration_alert_days' => 15],
            ['name' => 'Azúcar Blanca', 'measure_unit' => 'Kg', 'quantity' => 55, 'unit_price' => 900, 'expiration_date' => now()->addMonths(10)->toDateString(), 'expiration_alert_days' => 60],
            ['name' => 'Sal Refinada', 'measure_unit' => 'Kg', 'quantity' => 40, 'unit_price' => 650, 'expiration_date' => now()->addMonths(12)->toDateString(), 'expiration_alert_days' => 60],
            ['name' => 'Harina de Trigo', 'measure_unit' => 'Kg', 'quantity' => 36, 'unit_price' => 1200, 'expiration_date' => now()->addMonths(6)->toDateString(), 'expiration_alert_days' => 30],
            ['name' => 'Leche Semidescremada', 'measure_unit' => 'Litro', 'quantity' => 25, 'unit_price' => 1150, 'expiration_date' => now()->addDays(15)->toDateString(), 'expiration_alert_days' => 5],
            ['name' => 'Huevos de Pastoreo', 'measure_unit' => 'Cartón', 'quantity' => 20, 'unit_price' => 3200, 'expiration_date' => now()->addDays(12)->toDateString(), 'expiration_alert_days' => 3],
            ['name' => 'Café en Grano', 'measure_unit' => 'Kg', 'quantity' => 16, 'unit_price' => 6400, 'expiration_date' => now()->addMonths(9)->toDateString(), 'expiration_alert_days' => 30],
            ['name' => 'Mantequilla con Sal', 'measure_unit' => 'Kg', 'quantity' => 12, 'unit_price' => 4100, 'expiration_date' => now()->addDays(20)->toDateString(), 'expiration_alert_days' => 7],
        ];

        foreach ($supplies as $supply) {
            Supply::create($supply);
        }
    }
}
