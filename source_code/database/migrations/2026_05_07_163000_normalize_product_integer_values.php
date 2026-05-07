<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                foreach ($products as $product) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'reference_cost' => $this->normalizeInteger($product->reference_cost ?? 0),
                            'sale_price' => $this->normalizeInteger($product->sale_price ?? 0),
                            'tax_percentage' => $this->normalizePercentage($product->tax_percentage ?? 0),
                            'margin_percentage' => $this->normalizePercentage($product->margin_percentage ?? 0),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Values were normalized to integers; original decimal precision cannot be restored safely.
    }

    private function normalizeInteger(mixed $value): int
    {
        return (int) round((float) $value);
    }

    private function normalizePercentage(mixed $value): int
    {
        $numericValue = (float) $value;

        if ($numericValue > 0 && $numericValue <= 1) {
            $numericValue *= 100;
        }

        return (int) round($numericValue);
    }
};
