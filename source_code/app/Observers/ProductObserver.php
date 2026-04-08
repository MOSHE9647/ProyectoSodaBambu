<?php

namespace App\Observers;

use App\Actions\Inventory\GetLowStockProductsCount;
use App\Models\Product;

class ProductObserver
{
    public function __construct(protected GetLowStockProductsCount $getLowStockProductsCount) {}

    public function created(Product $product): void
    {
        $this->getLowStockProductsCount->execute();
    }

    public function updated(Product $product): void
    {
        $this->getLowStockProductsCount->execute();
    }

    public function deleted(Product $product): void
    {
        $this->getLowStockProductsCount->execute();
    }

    public function restored(Product $product): void
    {
        $this->getLowStockProductsCount->execute();
    }
}
