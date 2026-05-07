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
                $updates = [];

                foreach ($products as $product) {
                    $updates[] = [
                        'id' => $product->id,
                        'reference_cost' => $this->normalizeInteger($product->reference_cost ?? 0),
                        'sale_price' => $this->normalizeInteger($product->sale_price ?? 0),
                        'tax_percentage' => $this->normalizePercentage($product->tax_percentage ?? 0),
                        'margin_percentage' => $this->normalizePercentage($product->margin_percentage ?? 0),
                    ];
                }

                if (! empty($updates)) {
                    DB::table('products')->upsert(
                        $updates,
                        ['id'],
                        ['reference_cost', 'sale_price', 'tax_percentage', 'margin_percentage']
                    );
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')
            ->orderBy('id')
            ->chunkById(100, function ($products): void {
                $updates = [];

                foreach ($products as $product) {
                    $updates[] = [
                        'id' => $product->id,
                        'reference_cost' => round((float) ($product->reference_cost ?? 0), 2),
                        'sale_price' => round((float) ($product->sale_price ?? 0), 2),
                        'tax_percentage' => round(((float) ($product->tax_percentage ?? 0)) / 100, 2),
                        'margin_percentage' => round(((float) ($product->margin_percentage ?? 0)) / 100, 2),
                    ];
                }

                if (! empty($updates)) {
                    DB::table('products')->upsert(
                        $updates,
                        ['id'],
                        ['reference_cost', 'sale_price', 'tax_percentage', 'margin_percentage']
                    );
                }
            });
    }

    private function normalizeInteger(mixed $value): int
    {
        return (int) round((float) $value);
    }

    private function normalizePercentage(mixed $value): int
    {
        $numericValue = (float) $value;

        if (abs($numericValue) < 1) {
            $numericValue *= 100;
        }

        return (int) round($numericValue);
    }
};
