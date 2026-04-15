<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\GetLowStockProductsCount;
use App\Actions\Inventory\GetProductsAboutToExpireCount;
use App\Actions\Inventory\GetSuppliesAboutToExpireCount;
use App\Actions\Sale\CalculateDailySalesTrendAction;
use App\Actions\Sale\GetDailySalesDataAction;
use App\Actions\Sale\GetMonthlySalesDataAction;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        return redirect()->route('dashboard');
    }

    public function dashboard(
        GetLowStockProductsCount $getLowStockProductsCount,
        GetProductsAboutToExpireCount $getProductsAboutToExpireCount,
        CalculateDailySalesTrendAction $calculateDailySalesTrendAction,
        GetMonthlySalesDataAction $getMonthlySalesDataAction,
        GetDailySalesDataAction $getDailySalesDataAction,
        GetSuppliesAboutToExpireCount $getSuppliesAboutToExpireCount,
    ) {

        /**
         * Retrieves the count of products with stock levels at or below their minimum threshold.
         *
         * The result is cached indefinitely to improve performance on subsequent requests.
         * The cache key is 'low_stock_count' and will persist until manually cleared.
         */
        $totalMinStockProducts = Cache::rememberForever('low_stock_count', function () use ($getLowStockProductsCount) {
            return $getLowStockProductsCount->execute();
        });

        $aboutToExpireSupplies = Cache::remember('about_to_expire_supplies_count', now()->addDay(), function () use ($getSuppliesAboutToExpireCount) {
            return $getSuppliesAboutToExpireCount->execute();
        });

        $aboutToExpireProducts = Cache::remember('about_to_expire_products_count', now()->addDay(), function () use ($getProductsAboutToExpireCount) {
            return $getProductsAboutToExpireCount->execute();
        });

        $salesStats = Cache::remember('today_sales_stats', now()->addMinutes(10), function () use ($calculateDailySalesTrendAction) {
            return $calculateDailySalesTrendAction->execute();
        });

        $monthlyStats = Cache::remember('monthly_sales_stats', now()->addMinutes(10), function () use ($getMonthlySalesDataAction) {
            return $getMonthlySalesDataAction->execute();
        });

        $dailyStats = Cache::remember('daily_sales_stats', now()->addMinutes(10), function () use ($getDailySalesDataAction) {
            return $getDailySalesDataAction->execute();
        });

        return view('dashboard', [
            'aboutToExpireSupplies' => $aboutToExpireSupplies,
            'totalMinStockProducts' => $totalMinStockProducts,
            'aboutToExpireProducts' => $aboutToExpireProducts,
            ...$salesStats, ...$monthlyStats, ...$dailyStats,
        ]);
    }
}
