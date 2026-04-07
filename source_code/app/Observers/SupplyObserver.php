<?php

namespace App\Observers;

use App\Actions\Inventory\GetProductsAboutToExpireCount;
use App\Models\Supply;

class SupplyObserver
{
    public function __construct(protected GetProductsAboutToExpireCount $getProductsAboutToExpireCount)
    {
    }

    public function created(Supply $supply): void { $this->getProductsAboutToExpireCount->execute(); }
    public function updated(Supply $supply): void { $this->getProductsAboutToExpireCount->execute(); }
    public function deleted(Supply $supply): void { $this->getProductsAboutToExpireCount->execute(); }
    public function restored(Supply $supply): void { $this->getProductsAboutToExpireCount->execute(); }
}