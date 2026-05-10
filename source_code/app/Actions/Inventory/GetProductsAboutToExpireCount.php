<?php

namespace App\Actions\Inventory;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class GetProductsAboutToExpireCount
{
    public function execute(): int
    {
        $aboutToExpireCount = Product::query()
            ->whereHas('stock', fn ($q) => $q->where('current_stock', '>', 0))
            ->expiringSoon()
            ->count();

        Cache::forever('about_to_expire_products_count', $aboutToExpireCount);

        return $aboutToExpireCount;
    }
}
