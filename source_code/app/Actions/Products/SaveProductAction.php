<?php

namespace App\Actions\Products;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;

class SaveProductAction
{
    /**
     * Ejecuta la lógica de guardado/actualización del producto.
     * Devuelve el producto y el mensaje de éxito.
     *
     * @return array{0: Product, 1: string}
     */
    public function execute(array $payload, ?Product $product = null): array
    {
        $stockData = $this->extractStockData($payload);
        $productData = $this->applyPricingRules($payload);

        $message = $product ? 'Producto actualizado exitosamente.' : 'Producto creado exitosamente.';

        DB::transaction(function () use ($productData, $stockData, &$product, &$message) {
            if (! $product) {
                $product = ! empty($productData['barcode'])
                    ? Product::withTrashed()->where('barcode', $productData['barcode'])->first()
                    : null;

                if ($product?->trashed()) {
                    $product->restore();
                    $product->update($productData);
                    $message = 'Producto restaurado y actualizado exitosamente.';
                } else {
                    $product = Product::create($productData);
                }
            } else {
                $product->update($productData);
            }

            $this->syncInventoryStock($product, $productData, $stockData);
        });

        return [$product, $message];
    }

    private function applyPricingRules(array $productData): array
    {
        $type = $productData['type'] ?? null;

        if ($type === ProductType::MERCHANDISE->value) {
            $productData['sale_price'] = Product::calculateSalePrice(
                (float) $productData['reference_cost'],
                (float) $productData['tax_percentage'],
                (float) $productData['margin_percentage'],
            );

            return $productData;
        }

        $productData['reference_cost'] = 0;
        $productData['tax_percentage'] = 0;
        $productData['margin_percentage'] = 0;
        $productData['expiration_date'] = null;
        $productData['expiration_alert_days'] = 7;
        $productData['sale_price'] = (float) ($productData['sale_price'] ?? 0);

        return $productData;
    }

    private function extractStockData(array &$payload): array
    {
        $stockData = [
            'current_stock' => isset($payload['current_stock']) ? (int) $payload['current_stock'] : null,
            'minimum_stock' => isset($payload['minimum_stock']) ? (int) $payload['minimum_stock'] : null,
        ];

        unset($payload['current_stock'], $payload['minimum_stock']);

        return $stockData;
    }

    private function syncInventoryStock(Product $product, array $productData, array $stockData): void
    {
        if (! (bool) ($productData['has_inventory'] ?? false)) {
            return;
        }

        $stock = ProductStock::withTrashed()->firstOrNew([
            'product_id' => $product->id,
        ]);

        $wasTrashed = $stock->exists && $stock->trashed();
        $isNewStock = ! $stock->exists;

        $stock->fill([
            'current_stock' => $stockData['current_stock'] ?? ($isNewStock ? 0 : (int) ($stock->current_stock ?? 0)),
            'minimum_stock' => $stockData['minimum_stock'] ?? ($isNewStock ? 15 : (int) ($stock->minimum_stock ?? 15)),
        ]);
        $stock->save();

        if ($wasTrashed) {
            $stock->restore();
        }
    }
}
