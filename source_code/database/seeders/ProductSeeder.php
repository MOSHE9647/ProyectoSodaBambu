<?php

namespace Database\Seeders;

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
            ['category_id' => $catDesayuno, 'name' => 'Gallo Pinto Especial',          'current_stock' => 35,   'minimum_stock' => 12],
            ['category_id' => $catFuerte,   'name' => 'Casado con Carne en Salsa',     'current_stock' => 18,   'minimum_stock' => 10],
            ['category_id' => $catFuerte,   'name' => 'Arroz con Pollo',               'current_stock' => 22,   'minimum_stock' => 12],
            ['category_id' => $catFuerte,   'name' => 'Chifrijo Grande',               'current_stock' => 9,    'minimum_stock' => 10],
            ['category_id' => $catBebida,   'name' => 'Cerveza Imperial (Botella)',    'current_stock' => 50,   'minimum_stock' => 20],
            ['category_id' => $catBebida,   'name' => 'Cerveza Pilsen (Botella)',      'current_stock' => 16,   'minimum_stock' => 18],
            ['category_id' => $catFuerte,   'name' => 'Olla de Carne (Fin de semana)', 'current_stock' => null, 'minimum_stock' => null],
            ['category_id' => $catFuerte,   'name' => 'Hamburguesa Artesanal',         'current_stock' => 27,   'minimum_stock' => 14],
            ['category_id' => $catDesayuno, 'name' => 'Omelette con Tostadas',         'current_stock' => 14,   'minimum_stock' => 14],
            ['category_id' => $catFuerte,   'name' => 'Filete de Pescado al Ajillo',   'current_stock' => 11,   'minimum_stock' => 9],
        ];

        foreach ($products as $product) {
            $productData = $product;
            $currentStock = $productData['current_stock'];
            $minimumStock = $productData['minimum_stock'];

            unset($productData['current_stock'], $productData['minimum_stock']);
            $createdProduct = Product::factory()->create($productData);

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
