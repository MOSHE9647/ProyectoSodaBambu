<?php

namespace App\Actions\Products;

use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\SaleDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetTopSellingProductsAction
{
    /**
     * Retrieves the top 5 dishes with the highest accumulated sales quantity from paid orders.
     * Calculates the total sales volume and the total amount in colones per product.
     *
     * @param  int  $limit  Number of products to return (default 5)
     */
    public function execute(int $limit = 5): Collection
    {
        return SaleDetail::query()
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->where('sales.payment_status', PaymentStatus::PAID)
            ->whereNull('sales.deleted_at')
            ->whereNull('products.deleted_at')
            // Filter by product type: food and drinks
            ->whereIn('products.type', [ProductType::DISH, ProductType::DRINK])
            ->selectRaw('
                products.name,
                SUM(sale_details.quantity) as volume,
                SUM(sale_details.sub_total) as revenue
            ')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('volume')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->name,
                'volume' => (int) $item->volume,
                'revenue' => (int) $item->revenue,
            ]);
    }
}
