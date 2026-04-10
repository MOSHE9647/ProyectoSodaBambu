<?php

namespace App\Actions\Inventory;

use App\Models\Supply;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GetSuppliesAboutToExpireCount
{
    public function execute(): int
    {
        $today = Carbon::now()->startOfDay();

        $aboutToExpireCount = Supply::whereNotNull('expiration_date')
            ->where('quantity', '>', 0)
            ->where('expiration_date', '>=', $today)
            ->get()
            ->filter(function (Supply $supply) use ($today) {
                return $supply->expiration_date <= $today->copy()->addDays($supply->expiration_alert_days);
            })
            ->count();

        Cache::forever('about_to_expire_supplies_count', $aboutToExpireCount);

        return $aboutToExpireCount;
    }
}
