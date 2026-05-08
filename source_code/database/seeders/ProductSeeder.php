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
            // 5 Dishes
            ['category_id' => $catDesayuno, 'name' => 'Gallo Pinto Especial', 'type' => ProductType::DISH->value],
            ['category_id' => $catFuerte,   'name' => 'Casado con Carne en Salsa', 'type' => ProductType::DISH->value],
            ['category_id' => $catFuerte,   'name' => 'Arroz con Pollo', 'type' => ProductType::DISH->value],
            ['category_id' => $catFuerte,   'name' => 'Chifrijo Grande', 'type' => ProductType::DISH->value],
            ['category_id' => $catFuerte,   'name' => 'Hamburguesa Artesanal', 'type' => ProductType::DISH->value],

            // 5 Drinks (prepared in-house)
            ['category_id' => $catBebida,   'name' => 'Refresco Casero de Tamarindo', 'type' => ProductType::DRINK->value],
            ['category_id' => $catBebida,   'name' => 'Horchata de Conquito', 'type' => ProductType::DRINK->value],
            ['category_id' => $catBebida,   'name' => 'Fresco de Mora Natural', 'type' => ProductType::DRINK->value],
            ['category_id' => $catBebida,   'name' => 'Agua de Sapo (Especial)', 'type' => ProductType::DRINK->value],
            ['category_id' => $catBebida,   'name' => 'Cacao Caliente Casero', 'type' => ProductType::DRINK->value],

            // 5 Common merchandises
            ['category_id' => $catBebida,   'name' => 'Coca-Cola (350ml)', 'type' => ProductType::MERCHANDISE->value],
            ['category_id' => $catBebida,   'name' => 'Sprite (350ml)', 'type' => ProductType::MERCHANDISE->value],
            ['category_id' => $catBebida,   'name' => 'Fanta Naranja (350ml)', 'type' => ProductType::MERCHANDISE->value],
            ['category_id' => $catBebida,   'name' => 'Agua Mineral (500ml)', 'type' => ProductType::MERCHANDISE->value],
            ['category_id' => $catBebida,   'name' => 'Red Bull (250ml)', 'type' => ProductType::MERCHANDISE->value],
        ];

        foreach ($products as $product) {
            $productData = $product;
            $createdProduct = Product::factory()->create($productData);

            if (! $createdProduct->has_inventory) {
                continue;
            }

            ProductStock::factory()->create([
                'product_id' => $createdProduct->id,
            ]);
        }
    }
}
