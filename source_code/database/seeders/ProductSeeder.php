<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed the database using the respective seeders
        $this->call(CategorySeeder::class);

        // Get 3 Categories to assign to the Products
        $catDesayuno = Category::where('name', 'Desayunos')->first()->id;
        $catFuerte = Category::where('name', 'Platos Fuertes')->first()->id;
        $catBebida = Category::where('name', 'Licores')->first()->id;

        $products = [
            ['category_id' => $catDesayuno, 'barcode' => '2000000000001', 'name' => 'Gallo Pinto Especial', 'type' => ProductType::DISH, 'expiration_date' => now()->addDays(15)->toDateString(), 'has_inventory' => true, 'reference_cost' => 1200, 'tax_percentage' => 13, 'margin_percentage' => 150, 'sale_price' => 3000, 'current_stock' => 35, 'minimum_stock' => 12],
            ['category_id' => $catFuerte, 'barcode' => '2000000000002', 'name' => 'Casado con Carne en Salsa', 'type' => ProductType::DISH, 'expiration_date' => now()->addDays(12)->toDateString(), 'has_inventory' => true, 'reference_cost' => 1800, 'tax_percentage' => 13, 'margin_percentage' => 122, 'sale_price' => 4000, 'current_stock' => 18, 'minimum_stock' => 10],
            ['category_id' => $catFuerte, 'barcode' => '2000000000003', 'name' => 'Arroz con Pollo', 'type' => ProductType::DISH, 'expiration_date' => now()->addDays(10)->toDateString(), 'has_inventory' => true, 'reference_cost' => 1500, 'tax_percentage' => 13, 'margin_percentage' => 133, 'sale_price' => 3500, 'current_stock' => 22, 'minimum_stock' => 12],
            ['category_id' => $catFuerte, 'barcode' => '2000000000004', 'name' => 'Chifrijo Grande', 'type' => ProductType::DISH, 'expiration_date' => now()->addDays(8)->toDateString(), 'has_inventory' => true, 'reference_cost' => 2000, 'tax_percentage' => 13, 'margin_percentage' => 125, 'sale_price' => 4500, 'current_stock' => 9, 'minimum_stock' => 10],
            ['category_id' => $catBebida, 'barcode' => '2000000000005', 'name' => 'Cerveza Imperial (Botella)', 'type' => ProductType::DRINK, 'expiration_date' => now()->addMonths(4)->toDateString(), 'has_inventory' => true, 'reference_cost' => 950, 'tax_percentage' => 13, 'margin_percentage' => 57, 'sale_price' => 1500, 'current_stock' => 50, 'minimum_stock' => 20],
            ['category_id' => $catBebida, 'barcode' => '2000000000006', 'name' => 'Cerveza Pilsen (Botella)', 'type' => ProductType::DRINK, 'expiration_date' => now()->addMonths(5)->toDateString(), 'has_inventory' => true, 'reference_cost' => 950, 'tax_percentage' => 13, 'margin_percentage' => 57, 'sale_price' => 1500, 'current_stock' => 16, 'minimum_stock' => 18],
            ['category_id' => $catFuerte, 'barcode' => '2000000000007', 'name' => 'Olla de Carne (Fin de semana)', 'type' => ProductType::DISH, 'expiration_date' => null, 'has_inventory' => false, 'reference_cost' => 2200, 'tax_percentage' => 13, 'margin_percentage' => 104, 'sale_price' => 4500, 'current_stock' => null, 'minimum_stock' => null],
            ['category_id' => $catFuerte, 'barcode' => '2000000000008', 'name' => 'Hamburguesa Artesanal', 'type' => ProductType::DISH, 'expiration_date' => now()->addDays(9)->toDateString(), 'has_inventory' => true, 'reference_cost' => 2500, 'tax_percentage' => 13, 'margin_percentage' => 100, 'sale_price' => 5000, 'current_stock' => 27, 'minimum_stock' => 14],
            ['category_id' => $catDesayuno, 'barcode' => '2000000000009', 'name' => 'Omelette con Tostadas', 'type' => ProductType::DISH, 'expiration_date' => now()->addDays(7)->toDateString(), 'has_inventory' => true, 'reference_cost' => 1100, 'tax_percentage' => 13, 'margin_percentage' => 154, 'sale_price' => 2800, 'current_stock' => 14, 'minimum_stock' => 14],
            ['category_id' => $catFuerte, 'barcode' => '2000000000010', 'name' => 'Filete de Pescado al Ajillo', 'type' => ProductType::DISH, 'expiration_date' => now()->addDays(11)->toDateString(), 'has_inventory' => true, 'reference_cost' => 2800, 'tax_percentage' => 13, 'margin_percentage' => 96, 'sale_price' => 5500, 'current_stock' => 11, 'minimum_stock' => 9],
        ];

        foreach ($products as $product) {
            $productData = $product;
            $currentStock = $productData['current_stock'];
            $minimumStock = $productData['minimum_stock'];

            unset($productData['current_stock'], $productData['minimum_stock']);

            $createdProduct = Product::create($productData);

            if (! $createdProduct->has_inventory) {
                continue;
            }

            ProductStock::updateOrCreate([
                'product_id' => $createdProduct->id,
            ], [
                'current_stock' => $currentStock ?? rand(20, 100),
                'minimum_stock' => $minimumStock ?? 15,
            ]);
        }
    }
}
