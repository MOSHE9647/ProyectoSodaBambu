<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\SaleDetail;

class SaleDetailObserver
{
    /**
     * Handle the SaleDetail "created" event.
     */
    public function created(SaleDetail $saleDetail): void
    {
        $this->adjustStock($saleDetail, $saleDetail->quantity, 'decrement');
    }

    /**
     * Handle the SaleDetail "updated" event.
     */
    public function updated(SaleDetail $saleDetail): void
    {
        // If the product_id has changed, we need to adjust stock for both the old and new products
        if ($saleDetail->isDirty('product_id')) {
            // Restore stock for the old product
            $this->adjustStockById(
                $saleDetail->getOriginal('product_id'),
                $saleDetail->getOriginal('quantity'),
                'increment'
            );
            // Decrease stock for the new product
            $this->adjustStock($saleDetail, $saleDetail->quantity, 'decrement');

            return;
        }

        // If only the quantity has changed, adjust stock accordingly
        if ($saleDetail->isDirty('quantity')) {
            $quantityDiff = $saleDetail->quantity - $saleDetail->getOriginal('quantity');
            $method = $quantityDiff > 0 ? 'decrement' : 'increment';
            $this->adjustStock($saleDetail, abs($quantityDiff), $method);
        }
    }

    /**
     * Handle the SaleDetail "deleted" event.
     */
    public function deleted(SaleDetail $saleDetail): void
    {
        $this->adjustStock($saleDetail, $saleDetail->quantity, 'increment');
    }

    /**
     * Handle the SaleDetail "restored" event.
     */
    public function restored(SaleDetail $saleDetail): void
    {
        $this->adjustStock($saleDetail, $saleDetail->quantity, 'decrement');
    }

    /**
     * Handle the SaleDetail "force deleted" event.
     */
    public function forceDeleted(SaleDetail $saleDetail): void
    {
        //
    }

    /**
     * Adjust the stock level of a product based on the provided quantity and method.
     *
     * This method updates the current stock of a product if inventory tracking is enabled.
     * It uses dynamic method invocation to perform either increment or decrement operations.
     *
     * @param  SaleDetail  $saleDetail  The sale detail containing the product to adjust
     * @param  int  $quantity  The quantity to adjust the stock by
     * @param  string  $method  The method to invoke on the stock relationship ('increment' or 'decrement')
     */
    private function adjustStock(SaleDetail $saleDetail, int $quantity, string $method): void
    {
        $product = $saleDetail->product;
        if ($product?->has_inventory) {
            $product->stock()->$method('current_stock', $quantity);
        }
    }

    /**
     * Adjust the stock level of a product by its ID based on the provided quantity and method.
     *
     * This method updates the current stock of a product if inventory tracking is enabled.
     * It retrieves the product by ID and uses dynamic method invocation to perform either
     * increment or decrement operations.
     *
     * @param  int  $productId  The ID of the product to adjust stock for
     * @param  int  $quantity  The quantity to adjust the stock by
     * @param  string  $method  The method to invoke on the stock relationship ('increment' or 'decrement')
     */
    private function adjustStockById(int $productId, int $quantity, string $method): void
    {
        $product = Product::find($productId);
        if ($product?->has_inventory) {
            $product->stock()->$method('current_stock', $quantity);
        }
    }
}
