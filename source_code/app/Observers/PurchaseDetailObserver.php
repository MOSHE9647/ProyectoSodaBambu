<?php

namespace App\Observers;

use App\Models\PurchaseDetail;
use Illuminate\Support\Facades\Cache;

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

}
