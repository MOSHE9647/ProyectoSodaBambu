<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Supply;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(SupplierSeeder::class);
        $this->call(SupplySeeder::class);

        $suppliers = Supplier::all();
        $products = Product::where('has_inventory', true)->take(5)->get();
        $supplies = Supply::take(5)->get();

        for ($i = 1; $i <= 10; $i++) {
            $totalPurchase = rand(50000, 150000);

            $purchase = Purchase::create([
                'supplier_id' => $suppliers->random()->id,
                'invoice_number' => 'FAC-'.str_pad($i, 5, '0', STR_PAD_LEFT),
                'payment_status' => PaymentStatus::PAID,
                'date' => now()->subDays(rand(1, 30)),
                'total' => $totalPurchase,
            ]);

            // Add a Product to the Detail
            $product = $products->random();
            $productQuantity = rand(5, 20);
            $productPrice = (float) $product->reference_cost;

            $purchase->details()->create([
                'purchasable_id' => $product->id,
                'purchasable_type' => Product::class,
                'quantity' => $productQuantity,
                'unit_price' => $productPrice,
                'subtotal' => $productQuantity * $productPrice,
            ]);

            // Add a Supply to the Detail
            $supply = $supplies->random();
            $supplyQuantity = rand(2, 10);
            $supplyPrice = rand(2000, 8000);

            $purchase->details()->create([
                'purchasable_id' => $supply->id,
                'purchasable_type' => Supply::class,
                'quantity' => $supplyQuantity,
                'unit_price' => $supplyPrice,
                'subtotal' => $supplyQuantity * $supplyPrice,
            ]);
        }
    }
}
