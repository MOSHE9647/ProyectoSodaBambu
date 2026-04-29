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
     * @param int $limit Number of products to return (default 5)
     * @return Collection
     */
    public function execute(int $limit = 5): Collection
    {
        return SaleDetail::query()
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->where('sales.payment_status', PaymentStatus::PAID)
            //Filter by product type: food and drinks
            ->WhereIn('products.type',[
                ProductType::DISH->value,
                ProductType::DRINK->value,
            ])
            ->select(
                'products.name as product_name',
                DB::raw('SUM(sale_details.quantity) as total_volume'),
                DB::raw('SUM(sale_details.sub_total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_volume')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product_name,
                    'volume' => (float) $item->total_volume,
                    'revenue' => (float) $item->total_revenue,
                ];
            });
    }
}