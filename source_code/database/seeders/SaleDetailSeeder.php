<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Database\Seeder;

class SaleDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all sales and products to associate with sale details
        $sales = Sale::all();
        $products = Product::all();

        // Create sale details for each sale
        foreach ($sales as $sale) {
            // Ensure we don't try to create more sale details than available products
            $numberOfDetails = min(rand(1, 3), $products->count());

            // random() with a number will return a collection of random non-repeating items
            $randomProducts = $products->random($numberOfDetails);

            foreach ($randomProducts as $product) {
                SaleDetail::factory()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                ]);
            }
        }
    }
}
