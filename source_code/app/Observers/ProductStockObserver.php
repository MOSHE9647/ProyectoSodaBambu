<?php

namespace App\Observers;

use App\Actions\Inventory\GetLowStockProductsCount;
use App\Models\ProductStock;
use Illuminate\Support\Facades\Session;

class ProductStockObserver
{
    public function __construct(protected GetLowStockProductsCount $getLowStockProductsCount)
    {}

    public function created(ProductStock $productStock): void
    {
        $this->getLowStockProductsCount->execute();
    }

    /**
     * Handle the ProductStock "updated" event.
     */
    public function updated(ProductStock $productStock): void
    {
        if ($productStock->wasChanged('current_stock')) {
            $hasLowStock = $productStock->current_stock < $productStock->minimum_stock;
            if ($hasLowStock) {
                // Save a warning message in the session
                Session::flash('warning', "¡Stock bajo en {$productStock->product->name}!");
            }

            $this->getLowStockProductsCount->execute();
        }
    }

    public function deleted(ProductStock $productStock): void
    {
        $this->getLowStockProductsCount->execute();
    }
}
