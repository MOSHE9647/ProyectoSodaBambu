<?php

namespace App\Observers;

use App\Actions\Inventory\GetProductsAboutToExpireCount;
use App\Models\PurchaseDetail;

class PurchaseDetailObserver
{
    public function __construct(protected GetProductsAboutToExpireCount $getProductsAboutToExpireCount)
    {
    }

    /**
     * Handle the PurchaseDetail "created" event.
     */
    public function created(PurchaseDetail $purchaseDetail): void
    {
        $this->getProductsAboutToExpireCount->execute();
    }

    /**
     * Handle the PurchaseDetail "updated" event.
     */
    public function updated(PurchaseDetail $purchaseDetail): void
    {
        $this->getProductsAboutToExpireCount->execute();
    }

    /**
     * Handle the PurchaseDetail "deleted" event.
     */
    public function deleted(PurchaseDetail $purchaseDetail): void
    {
        $this->getProductsAboutToExpireCount->execute();
    }
}