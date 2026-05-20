<?php

namespace App\Observers;

use App\Actions\Inventory\GetProductsAboutToExpireCount;
use App\Models\Product;
use App\Models\PurchaseDetail;

class PurchaseDetailObserver
{
    public function __construct(protected GetProductsAboutToExpireCount $getProductsAboutToExpireCount) {}

    /**
     * Handle the PurchaseDetail "created" event.
     */
    public function created(PurchaseDetail $purchaseDetail): void
    {
        $this->adjustStock($purchaseDetail, $purchaseDetail->quantity, 'increment');
        
        // Actualizamos el costo maestro del producto o insumo al precio de esta compra
        $this->updateMasterCost($purchaseDetail);

        $this->getProductsAboutToExpireCount->execute();
    }

    /**
     * Handle the PurchaseDetail "updated" event.
     */
    public function updated(PurchaseDetail $purchaseDetail): void
    {
        // Si el producto o tipo ha cambiado, ajustamos el stock para el anterior y el nuevo
        if ($purchaseDetail->isDirty(['purchasable_id', 'purchasable_type'])) {
            // Revertimos el stock del producto anterior (decrementamos la cantidad comprada)
            $this->adjustStockById(
                $purchaseDetail->getOriginal('purchasable_type'),
                (int) $purchaseDetail->getOriginal('purchasable_id'),
                (int) $purchaseDetail->getOriginal('quantity'),
                'decrement'
            );

            // Incrementamos el stock del nuevo producto
            $this->adjustStock($purchaseDetail, $purchaseDetail->quantity, 'increment');
            
            // Actualizamos el costo maestro del nuevo producto/insumo
            $this->updateMasterCost($purchaseDetail);
        } elseif ($purchaseDetail->isDirty('quantity')) {
            // Si solo cambió la cantidad, calculamos la diferencia
            $quantityDiff = $purchaseDetail->quantity - $purchaseDetail->getOriginal('quantity');
            $method = $quantityDiff > 0 ? 'increment' : 'decrement';
            $this->adjustStock($purchaseDetail, abs($quantityDiff), $method);
        } elseif ($purchaseDetail->isDirty('unit_price')) {
            // Si se corrigió el precio unitario en la edición, actualizamos el maestro
            $this->updateMasterCost($purchaseDetail);
        }

        $this->getProductsAboutToExpireCount->execute();
    }

    /**
     * Handle the PurchaseDetail "deleted" event.
     */
    public function deleted(PurchaseDetail $purchaseDetail): void
    {
        // Al eliminar una compra, restamos la cantidad del stock
        $this->adjustStock($purchaseDetail, $purchaseDetail->quantity, 'decrement');

        $this->getProductsAboutToExpireCount->execute();
    }

    /**
     * Handle the PurchaseDetail "restored" event.
     */
    public function restored(PurchaseDetail $purchaseDetail): void
    {
        // Al restaurar, volvemos a incrementar el stock
        $this->adjustStock($purchaseDetail, $purchaseDetail->quantity, 'increment');

        $this->getProductsAboutToExpireCount->execute();
    }

    /**
     * Adjust the stock level of a product based on the provided quantity and method.
     *
     * This method updates the current stock of a product if inventory tracking is enabled.
     * It uses dynamic method invocation to perform either increment or decrement operations.
     *
     * @param  PurchaseDetail  $purchaseDetail  The purchase detail containing the product to adjust
     * @param  int  $quantity  The quantity to adjust the stock by
     * @param  string  $method  The method to invoke on the stock relationship ('increment' or 'decrement')
     */
    private function adjustStock(PurchaseDetail $purchaseDetail, int $quantity, string $method): void
    {
        $product = $purchaseDetail->purchasable()->withTrashed()->first();
        
        if ($product instanceof Product && $product->has_inventory) {
            $product->stock()->$method('current_stock', $quantity);
        }
    }

    /**
     * Updates the reference cost of a Product or the unit price of a Supply
     * based on the latest purchase detail price.
     */
    private function updateMasterCost(PurchaseDetail $purchaseDetail): void
    {
        $item = $purchaseDetail->purchasable;

        if (! $item) {
            return;
        }

        if ($item instanceof Product) {
            if ($item->type !== \App\Enums\ProductType::MERCHANDISE) {
                // Updates Product's Sale Price.
                // Since no Merchandise products don't have a reference cost, 
                // they use the unit price of the last purchase as their sale price
                $item->update(['sale_price' => $purchaseDetail->unit_price]);
            } else {
                // Updates Merchandise's Reference Cost.
                $item->update(['reference_cost' => $purchaseDetail->unit_price]);
                $item->update(['sale_price' => Product::calculateSalePrice(
                    $purchaseDetail->unit_price,
                    $item->tax_percentage,
                    $item->margin_percentage
                )]);
            }
        } elseif ($item instanceof \App\Models\Supply) {
            // Updates Supply's Unit Price.
            $item->update(['unit_price' => $purchaseDetail->unit_price]);
        }
    }

    /**
     * Adjust the stock level of a product by its ID based on the provided quantity and method.
     *
     * This method updates the current stock of a product if inventory tracking is enabled.
     * It retrieves the product by ID and uses dynamic method invocation to perform either
     * increment or decrement operations.
     *
     * @param  string|null  $type  The morph class type
     * @param  int|null  $id  The ID of the product to adjust stock for
     * @param  int  $quantity  The quantity to adjust the stock by
     * @param  string  $method  The method to invoke on the stock relationship ('increment' or 'decrement')
     */
    private function adjustStockById(?string $type, ?int $id, int $quantity, string $method): void
    {
        // Validamos que el tipo sea Producto y que tengamos un ID válido
        if ($type !== Product::class || ! $id) {
            return;
        }

        $product = Product::find($id);

        if ($product?->has_inventory) {
            $product->stock()->$method('current_stock', $quantity);
        }
    }
}
