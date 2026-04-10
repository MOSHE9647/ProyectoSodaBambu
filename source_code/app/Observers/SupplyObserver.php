<?php

namespace App\Observers;

use App\Actions\Inventory\GetSuppliesAboutToExpireCount;
use App\Models\Supply;

class SupplyObserver
{
    public function __construct(protected GetSuppliesAboutToExpireCount $getSuppliesAboutToExpireCount) {}

    public function created(Supply $supply): void
    {
        $this->getSuppliesAboutToExpireCount->execute();
    }

    public function updated(Supply $supply): void
    {
        $this->getSuppliesAboutToExpireCount->execute();
    }

    public function deleted(Supply $supply): void
    {
        $this->getSuppliesAboutToExpireCount->execute();
    }

    public function restored(Supply $supply): void
    {
        $this->getSuppliesAboutToExpireCount->execute();
    }
}
