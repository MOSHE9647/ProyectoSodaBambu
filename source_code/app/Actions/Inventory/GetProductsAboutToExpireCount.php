<?php

namespace App\Actions\Inventory;

use App\Models\PurchaseDetail;
use App\Models\Supply;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GetProductsAboutToExpireCount
{
    /**
     * 
     */
    public function execute(): int
    {
        $today = Carbon::now()->startOfDay();
        $expirationDateIn7Days = Carbon::now()->addDays(7)->endOfDay();

        $aboutToExpireCount = PurchaseDetail::where('purchasable_type', Supply::class)
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [$today, $expirationDateIn7Days])
            ->whereHasMorph('purchasable', [Supply::class])
            ->count();

        Cache::forever('about_to_expire_count', $aboutToExpireCount);

        return $aboutToExpireCount;
    }
}