<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch the necessary data for creating purchases
        $users = User::take(10)->get();
        $suppliers = Supplier::take(10)->get();
        $products = Product::where('has_inventory', true)->take(5)->get();
        $supplies = Supply::take(5)->get();

        for ($i = 1; $i <= 10; $i++) {
            // Product details for the purchase
            $product = $products->random();
            $productUnitPrice = (float) $product->reference_cost;
            $productQuantity = rand(5, 20);
            $productSubTotal = $productUnitPrice * $productQuantity;

            // Supply details for the purchase
            $supply = $supplies->random();
            $supplyQuantity = rand(2, 10);
            $supplyUnitPrice = rand(2000, 8000);
            $supplySubTotal = $supplyUnitPrice * $supplyQuantity;

            // Create the purchase with the calculated total
            $purchase = Purchase::factory()->create([
                'user_id' => $users->random()->id,
                'supplier_id' => $suppliers->random()->id,
                'total' => $productSubTotal + $supplySubTotal,
            ]);

            // Add a Product to the Detail
            $purchase->details()->create([
                'purchasable_id' => $product->id,
                'purchasable_type' => Product::class,
                'quantity' => $productQuantity,
                'unit_price' => $productUnitPrice,
                'sub_total' => $productSubTotal,
            ]);

            // Add a Supply to the Detail
            $purchase->details()->create([
                'purchasable_id' => $supply->id,
                'purchasable_type' => Supply::class,
                'quantity' => $supplyQuantity,
                'unit_price' => $supplyUnitPrice,
                'sub_total' => $supplySubTotal,
            ]);
        }
    }
}
