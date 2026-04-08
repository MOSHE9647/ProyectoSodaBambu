<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Seeder;

class ProductStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all products that have inventory
        $products = Product::where('has_inventory', true)->get();

        // Create stock entries only when they are missing.
        // ProductSeeder is the source of truth for predefined demo stock values.
        foreach ($products as $product) {
            ProductStock::firstOrCreate([
                'product_id' => $product->id,
            ], [
                'current_stock' => rand(20, 100),
                'minimum_stock' => 15,
            ]);
        }
    }
}
