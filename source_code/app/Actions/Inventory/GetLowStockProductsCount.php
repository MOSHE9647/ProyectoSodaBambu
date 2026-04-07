<?php

namespace App\Actions\Inventory;

use App\Models\ProductStock;
use Illuminate\Support\Facades\Cache;

class GetLowStockProductsCount
{
    /**
     * 
     *
     * @return int
     */
    public function execute(): int
    {
        $lowStockCount = ProductStock::whereHas('product', function ($query) {
            $query->whereNull('deleted_at');
        })
        ->whereRaw('current_stock <= minimum_stock')
        ->count();

        Cache::forever('low_stock_count', $lowStockCount);

        return $lowStockCount;
    }
}
