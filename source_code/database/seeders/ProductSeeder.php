<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['category_id' => $catDesayuno, 'name' => 'Gallo Pinto Especial', 'type' => ProductType::DISH, 'has_inventory' => true, 'reference_cost' => 1200, 'tax_percentage' => 13, 'margin_percentage' => 150, 'sale_price' => 3000],
            ['category_id' => $catFuerte, 'name' => 'Casado con Carne en Salsa', 'type' => ProductType::DISH, 'has_inventory' => true, 'reference_cost' => 1800, 'tax_percentage' => 13, 'margin_percentage' => 122, 'sale_price' => 4000],
            ['category_id' => $catFuerte, 'name' => 'Arroz con Pollo', 'type' => ProductType::DISH, 'has_inventory' => true, 'reference_cost' => 1500, 'tax_percentage' => 13, 'margin_percentage' => 133, 'sale_price' => 3500],
            ['category_id' => $catFuerte, 'name' => 'Chifrijo Grande', 'type' => ProductType::DISH, 'has_inventory' => true, 'reference_cost' => 2000, 'tax_percentage' => 13, 'margin_percentage' => 125, 'sale_price' => 4500],
            ['category_id' => $catBebida, 'name' => 'Cerveza Imperial (Botella)', 'type' => ProductType::DRINK, 'has_inventory' => true, 'reference_cost' => 950, 'tax_percentage' => 13, 'margin_percentage' => 57, 'sale_price' => 1500],
            ['category_id' => $catBebida, 'name' => 'Cerveza Pilsen (Botella)', 'type' => ProductType::DRINK, 'has_inventory' => true, 'reference_cost' => 950, 'tax_percentage' => 13, 'margin_percentage' => 57, 'sale_price' => 1500],
            ['category_id' => $catFuerte, 'name' => 'Olla de Carne (Fin de semana)', 'type' => ProductType::DISH, 'has_inventory' => false, 'reference_cost' => 2200, 'tax_percentage' => 13, 'margin_percentage' => 104, 'sale_price' => 4500],
            ['category_id' => $catFuerte, 'name' => 'Hamburguesa Artesanal', 'type' => ProductType::DISH, 'has_inventory' => true, 'reference_cost' => 2500, 'tax_percentage' => 13, 'margin_percentage' => 100, 'sale_price' => 5000],
            ['category_id' => $catDesayuno, 'name' => 'Omelette con Tostadas', 'type' => ProductType::DISH, 'has_inventory' => true, 'reference_cost' => 1100, 'tax_percentage' => 13, 'margin_percentage' => 154, 'sale_price' => 2800],
            ['category_id' => $catFuerte, 'name' => 'Filete de Pescado al Ajillo', 'type' => ProductType::DISH, 'has_inventory' => true, 'reference_cost' => 2800, 'tax_percentage' => 13, 'margin_percentage' => 96, 'sale_price' => 5500],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
