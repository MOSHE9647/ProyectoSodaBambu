<?php

namespace App\Actions\Inventory;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GetProductsAboutToExpireCount
{
    
    public function execute(): int
    {
        $today = Carbon::now()->startOfDay();

        $aboutToExpireCount = Product::whereNotNull('expiration_date')
            ->whereHas('stock', function ($query) {
                $query->where('current_stock', '>', 0);
            })
            ->where('expiration_date', '>=', $today)
            ->get()
            ->filter(function (Product $product) use ($today) {
                // We use the expiration_alert_days field from the database
                return $product->expiration_date <= $today->copy()->addDays($product->expiration_alert_days);
            })
            ->count();

        Cache::forever('about_to_expire_products_count', $aboutToExpireCount);

        return $aboutToExpireCount;
    }
}