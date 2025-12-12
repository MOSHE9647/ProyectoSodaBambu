<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Manually create suppliers with realistic data
        $suppliers = [
            [
                'name'  => 'Mayca Foodservice',
                'phone' => '+506 2209 0500',
                'email' => 'serviciocliente@mayca.com',
            ],
            [
                'name'  => 'Belca Foodservice',
                'phone' => '+506 2509 2011',
                'email' => 'ventas@belca.co.cr', 
            ],
            [
                'name'  => 'Agroinsumos del Trópico',
                'phone' => '+506 2262 3674',
                'email' => 'ventas@agroinsumosdeltropico.com',
            ],
            [
                'name'  => 'Tres Jotas (Carnes)',
                'phone' => '+506 2772 0002',
                'email' => 'info@tresjotas.com',
            ],
            [
                'name'  => 'Cooperativa Dos Pinos',
                'phone' => '+506 2508 2525',
                'email' => 'centrodecontactos@dospinos.com',
            ],
            [
                'name'  => 'Frutica',
                'phone' => '+506 2282 6039',
                'email' => 'mercadeo@fruticacr.com',
            ],
            [
                'name'  => 'La Maquila Lama',
                'phone' => '+506 4101 0100',
                'email' => 'recepcion@maquilalama.com',
            ],
            [
                'name'  => 'Total Seafood',
                'phone' => '+506 2438 3958',
                'email' => 'servicioalcliente@totalseafood.co.cr',
            ],
            [
                'name'  => 'Green Center',
                'phone' => '+506 8858 2029',
                'email' => 'info@greencentercr.com',
            ],
            [
                'name'  => 'Alpiste (Distribuidora de Vinos y Alimentos)',
                'phone' => '+506 2215 3332',
                'email' => 'info@alpiste.co.cr',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}