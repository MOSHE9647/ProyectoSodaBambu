<?php

namespace App\Observers;

use App\Actions\Inventory\GetLowStockProductsCount;
use App\Actions\Inventory\GetProductsAboutToExpireCount;
use App\Models\Product;

class ProductObserver
{
    public function __construct(protected GetLowStockProductsCount $getLowStockProductsCount, protected GetProductsAboutToExpireCount $getProductsAboutToExpireCount) {}

    public function created(Product $product): void
    {
        $this->refreshCache();
    }

    public function updated(Product $product): void
    {
        $this->refreshCache();

    }

    public function deleted(Product $product): void
    {
        $this->refreshCache();
    }

    public function restored(Product $product): void
    {
        $this->refreshCache();
    }

    public function refreshCache(): void
    {
        Cache::forget('low_stock_count');
        Cache::forget('about_to_expire_products_count');

        $this->getLowStockProductsCount->execute();
        $this->getProductsAboutToExpireCount->execute();
    }
}
