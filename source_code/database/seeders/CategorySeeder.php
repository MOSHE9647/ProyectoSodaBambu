<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            ['name' => 'Bebidas'],
            ['name' => 'Comidas'],
            ['name' => 'Postres'],
            ['name' => 'Aperitivos'],
            ['name' => 'Ensaladas'],
            ['name' => 'Sopas'],
            ['name' => 'Carnes'],
            ['name' => 'Pescados y Mariscos'],
            ['name' => 'Vegetariano'],
            ['name' => 'Vegano'],
        ];

        // Insert categories into the database
        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
