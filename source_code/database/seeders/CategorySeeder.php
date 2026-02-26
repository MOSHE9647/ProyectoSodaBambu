<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Manually create categories with realistic data
        $categories = [
            ['name' => 'Bebidas', 'description' => 'Bebidas alcohólicas y no alcohólicas'],
            ['name' => 'Comidas', 'description' => 'Platos principales y acompañamientos'],
            ['name' => 'Postres', 'description' => 'Dulces y postres variados'],
            ['name' => 'Aperitivos', 'description' => 'Snacks y aperitivos para compartir'],
            ['name' => 'Ensaladas', 'description' => 'Ensaladas frescas y saludables'],
            ['name' => 'Sopas', 'description' => 'Sopas calientes y frías'],
            ['name' => 'Carnes', 'description' => 'Carnes de res, cerdo, pollo y otros animales'],
            ['name' => 'Pescados y Mariscos', 'description' => 'Pescados frescos y mariscos variados'],
            ['name' => 'Vegetariano', 'description' => 'Platos vegetarianos sin productos animales'],
            ['name' => 'Vegano', 'description' => 'Platos veganos sin productos de origen animal'],
        ];

        // Insert categories into the database
        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
