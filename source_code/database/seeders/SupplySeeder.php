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
            ['name' => 'Arroz Blanco', 'measure_unit' => 'Saco'],
            ['name' => 'Frijoles Negros', 'measure_unit' => 'Saco'],
            ['name' => 'Aceite Vegetal', 'measure_unit' => 'Pichinga'],
            ['name' => 'Azúcar Blanca', 'measure_unit' => 'Kg'],
            ['name' => 'Sal Refinada', 'measure_unit' => 'Kg'],
            ['name' => 'Harina de Trigo', 'measure_unit' => 'Kg'],
            ['name' => 'Leche Semidescremada', 'measure_unit' => 'Litro'],
            ['name' => 'Huevos de Pastoreo', 'measure_unit' => 'Cartón'],
            ['name' => 'Café en Grano', 'measure_unit' => 'Kg'],
            ['name' => 'Mantequilla con Sal', 'measure_unit' => 'Kg'],
        ];

        foreach ($supplies as $supply) {
            Supply::create($supply);
        }
    }
}
