<?php

namespace Database\Seeders;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        // Obtener IDs de categorías
        $cats = Category::pluck('id', 'name');

        $products = [
            // ARROCES
            ['category_id' => $cats['Arroces'], 'name' => 'Arroz Cantonés', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Arroces'], 'name' => 'Arroz con Camarones', 'sale_price' => 4000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Arroces'], 'name' => 'Arroz con Pollo', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Arroces'], 'name' => 'Arroz con Carne', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],

            // ESPECIALES
            ['category_id' => $cats['Especiales'], 'name' => 'Sopa Negra', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Especiales'], 'name' => 'Sopa de Pollo', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Especiales'], 'name' => 'Fajitas (Pollo, Cerdo, Res o Mixtas)', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Especiales'], 'name' => 'Filete de pollo o pescado', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Especiales'], 'name' => 'Espagueti en Salsa Blanca', 'sale_price' => 4000, 'type' => ProductType::DISH->value, 'has_inventory' => false],

            // BEBIDAS
            ['category_id' => $cats['Bebidas'], 'name' => 'Café Negro', 'sale_price' => 750, 'type' => ProductType::DRINK->value, 'has_inventory' => false],
            ['category_id' => $cats['Bebidas'], 'name' => 'Café con Leche', 'sale_price' => 1000, 'type' => ProductType::DRINK->value, 'has_inventory' => false],
            ['category_id' => $cats['Bebidas'], 'name' => 'Naturales en Agua', 'sale_price' => 1000, 'type' => ProductType::DRINK->value, 'has_inventory' => false],
            ['category_id' => $cats['Bebidas'], 'name' => 'Batidos en Leche', 'sale_price' => 1500, 'type' => ProductType::DRINK->value, 'has_inventory' => false],
            ['category_id' => $cats['Bebidas'], 'name' => 'Fresco de la Casa', 'sale_price' => 500, 'type' => ProductType::DRINK->value, 'has_inventory' => false],
            ['category_id' => $cats['Bebidas'], 'name' => 'Agua Dulce en Agua', 'sale_price' => 750, 'type' => ProductType::DRINK->value, 'has_inventory' => false],
            ['category_id' => $cats['Bebidas'], 'name' => 'Agua Dulce en Leche', 'sale_price' => 1000, 'type' => ProductType::DRINK->value, 'has_inventory' => false],

            // DESAYUNOS
            ['category_id' => $cats['Desayunos'], 'name' => 'Gallo Pinto Económico', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Desayunos'], 'name' => 'Gallo Pinto Completo', 'sale_price' => 3000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Desayunos'], 'name' => 'Empanadas (Pollo, queso o frijol)', 'sale_price' => 1200, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Desayunos'], 'name' => 'Empanada de carne', 'sale_price' => 1500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Desayunos'], 'name' => 'Totilla Alineada(Con refresco)', 'sale_price' => 1500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Desayunos'], 'name' => 'Sandwich (Pollo, Carne o jamon y queso)', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],

            // ALMUERZOS
            // Variantes de Casado Económico
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Económico (Pollo en salsa)', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Económico (Carne en salsa)', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Económico (Bistec de res)', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Económico (Bistec de cerdo)', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Económico (Chuleta)', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Económico (Filete de pollo)', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            // Variantes de Casado Completo
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Completo (Pollo en salsa)', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Completo (Carne en salsa)', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Completo (Bistec de res)', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Completo (Bistec de cerdo)', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Completo (Chuleta)', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Completo (Filete de pollo)', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Almuerzos'], 'name' => 'Casado Completo (Filete de pescado)', 'sale_price' => 3500, 'type' => ProductType::DISH->value, 'has_inventory' => false],

            // COMIDAS RÁPIDAS
            ['category_id' => $cats['Comidas Rápidas'], 'name' => 'Hamburguesa (con papas y refresco)', 'sale_price' => 2500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Comidas Rápidas'], 'name' => 'Tacos (con papas y refresco)', 'sale_price' => 2500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Comidas Rápidas'], 'name' => 'Papas Fritas o Salchipapas(con refresco)', 'sale_price' => 1500, 'type' => ProductType::DISH->value, 'has_inventory' => false],
            ['category_id' => $cats['Comidas Rápidas'], 'name' => 'Empanada Arreglada(con refresco)', 'sale_price' => 2000, 'type' => ProductType::DISH->value, 'has_inventory' => false],

            // EXTRAS
            ['category_id' => $cats['Empaques'], 'name' => 'Empaque para llevar', 'sale_price' => 200, 'type' => ProductType::PACKAGED->value, 'has_inventory' => false],
        ];

        foreach ($products as $product) {
            Product::factory()->create($product);
        }
    }
}
