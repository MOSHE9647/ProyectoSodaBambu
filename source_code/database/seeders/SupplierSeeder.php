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
            ['name' => 'Dos Pinos', 'phone' => '2437-3000', 'email' => 'ventas@dospinos.com'],
            ['name' => 'Mayca Distribuidores', 'phone' => '2205-5100', 'email' => 'servicioalcliente@mayca.com'],
            ['name' => 'Belca Foodservice', 'phone' => '2239-0111', 'email' => 'pedidos@belca.co.cr'],
            ['name' => 'Distribuidora Pipasa', 'phone' => '2505-1500', 'email' => 'contacto@pipasa.com'],
            ['name' => 'Coca-Cola FEMSA CR', 'phone' => '800-262-2265', 'email' => 'servicio@femsa.com'],
            ['name' => 'Carnes Don Fernando', 'phone' => '2282-1212', 'email' => 'ventas@donfernando.com'],
            ['name' => 'Walmart México y CA', 'phone' => '800-8000-722', 'email' => 'proveedores@walmart.com'],
            ['name' => 'Panadería Musmanni', 'phone' => '2272-2022', 'email' => 'pedidos@musmanni.com'],
            ['name' => 'Café Britt Costa Rica', 'phone' => '2277-1500', 'email' => 'info@britt.com'],
            ['name' => 'Feria del Agricultor Local', 'phone' => '2200-1122', 'email' => 'ventas@ferialocal.com'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
