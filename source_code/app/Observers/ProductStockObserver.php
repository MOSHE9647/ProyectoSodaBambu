<?php

namespace App\Observers;

use App\Models\ProductStock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class ProductStockObserver
{
    
    public function created(ProductStock $productStock): void
    {
        $this->countLowStockProducts();
    }

    /**
     * Handle the ProductStock "updated" event.
     */
    public function updated(ProductStock $productStock): void
    {
        if($productStock->wasChanged('current_stock')){
            $hasLowStock = $productStock->current_stock < $productStock->minimum_stock;
            if($hasLowStock){
                 // Save a warning message in the session
                Session::flash('warning', "¡Stock bajo en {$productStock->product->name}!");
            }

            $this->countLowStockProducts();
        }
    }

    public function deleted(ProductStock $productStock): void
    {
        $this->countLowStockProducts();
    }


    /**
     * Count the number of products with low stock levels.
     *
     * Queries all products where the current stock is less than or equal to the
     * minimum stock threshold and stores the count in cache indefinitely.
     */
    private function countLowStockProducts()
    {
        $lowStockCount = ProductStock::whereRaw('current_stock <= minimum_stock')->count();
        Cache::forever('low_stock_count', $lowStockCount);
    }

    
}
