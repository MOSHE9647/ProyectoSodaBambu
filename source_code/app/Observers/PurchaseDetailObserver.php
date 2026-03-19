<?php

namespace App\Observers;

use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PurchaseDetailObserver
{
    /**
     * Handle the PurchaseDetail "created" event.
     */
    public function created(PurchaseDetail $purchaseDetail): void
    {
        $this->countProductsAboutToExpire();
    }

    /**
     * Handle the PurchaseDetail "updated" event.
     */
    public function updated(PurchaseDetail $purchaseDetail): void
    {
        $this->countProductsAboutToExpire();
    }

    /**
     * Handle the PurchaseDetail "deleted" event.
     */
    public function deleted(PurchaseDetail $purchaseDetail): void
    {
        $this->countProductsAboutToExpire();
    }

    private function countProductsAboutToExpire()
    {
        $today = Carbon::now()->startOfDay();
        $expirationDateIn7Days = Carbon::now()->addDays(7)->endOfDay();

        $aboutToExpireCount = PurchaseDetail::whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [$today, $expirationDateIn7Days])
            ->count();
        Cache::forever('about_to_expire_count', $aboutToExpireCount);
    }
}
