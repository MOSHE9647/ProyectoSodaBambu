<?php

use App\Models\Product;
use App\Models\Supply;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $details = DB::table('purchase_details')
            ->select([
                'purchasable_id',
                'purchasable_type',
                'quantity',
                'unit_price',
                'expiration_date',
                'created_at',
                'id',
            ])
            ->whereNull('deleted_at')
            ->whereIn('purchasable_type', [Product::class, Supply::class])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $latestProducts = [];
        $latestSupplies = [];

        foreach ($details as $detail) {
            if ($detail->purchasable_type === Product::class) {
                $latestProducts[(int) $detail->purchasable_id] = $detail;
                continue;
            }

            if ($detail->purchasable_type === Supply::class) {
                $latestSupplies[(int) $detail->purchasable_id] = $detail;
            }
        }

        foreach ($latestProducts as $productId => $detail) {
            $payload = [
                'reference_cost' => $detail->unit_price,
            ];

            if ($detail->expiration_date !== null) {
                $payload['expiration_date'] = $detail->expiration_date;
            }

            DB::table('products')->where('id', $productId)->update($payload);
        }

        foreach ($latestSupplies as $supplyId => $detail) {
            DB::table('supplies')
                ->where('id', $supplyId)
                ->update([
                    'quantity' => (int) $detail->quantity,
                    'unit_price' => $detail->unit_price,
                    'expiration_date' => $detail->expiration_date,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backfill migration is intentionally not reversible.
    }
};
