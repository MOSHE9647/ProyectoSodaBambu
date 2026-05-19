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
        $categories = [
            ['name' => 'Desayunos', 'description' => 'Gallo pinto y opciones mañaneras'],
            ['name' => 'Almuerzos', 'description' => 'Casados y platos del día'],
            ['name' => 'Arroces', 'description' => 'Variedad de arroces acompañados'],
            ['name' => 'Especiales', 'description' => 'Sopas, fajitas y platos especiales'],
            ['name' => 'Bebidas', 'description' => 'Café, naturales y batidos'],
            ['name' => 'Comidas Rápidas', 'description' => 'Hamburguesas, tacos y empanadas'],
            ['name' => 'Empaques', 'description' => 'Cargos adicionales por empaque'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
