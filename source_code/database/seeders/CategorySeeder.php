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
            ['name' => 'Desayunos', 'description' => 'Opciones para iniciar el día'],
            ['name' => 'Entradas', 'description' => 'Bocas y acompañamientos'],
            ['name' => 'Platos Fuertes', 'description' => 'Platos principales'],
            ['name' => 'Bebidas Naturales', 'description' => 'Frutas de temporada'],
            ['name' => 'Bebidas Gaseosas', 'description' => 'Refrescos embotellados'],
            ['name' => 'Cafetería', 'description' => 'Café de especialidad y repostería'],
            ['name' => 'Postres', 'description' => 'Dulces y helados'],
            ['name' => 'Licores', 'description' => 'Cervezas y coctelería'],
            ['name' => 'Menú Infantil', 'description' => 'Porciones para niños'],
            ['name' => 'Extras', 'description' => 'Adicionales para los platos'],
        ];

        // Insert categories into the database
        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
