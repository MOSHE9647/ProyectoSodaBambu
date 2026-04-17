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
            ['category_id' => $catDesayuno, 'name' => 'Gallo Pinto Especial'],
            ['category_id' => $catFuerte,   'name' => 'Casado con Carne en Salsa'],
            ['category_id' => $catFuerte,   'name' => 'Arroz con Pollo'],
            ['category_id' => $catFuerte,   'name' => 'Chifrijo Grande'],
            ['category_id' => $catBebida,   'name' => 'Cerveza Imperial (Botella)'],
            ['category_id' => $catBebida,   'name' => 'Cerveza Pilsen (Botella)'],
            ['category_id' => $catFuerte,   'name' => 'Olla de Carne (Fin de semana)'],
            ['category_id' => $catFuerte,   'name' => 'Hamburguesa Artesanal'],
            ['category_id' => $catDesayuno, 'name' => 'Omelette con Tostadas'],
            ['category_id' => $catFuerte,   'name' => 'Filete de Pescado al Ajillo'],
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
