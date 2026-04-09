<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\GetLowStockProductsCount;
use App\Actions\Inventory\GetProductsAboutToExpireCount;
use App\Actions\Sale\CalculateDailySalesTrendAction;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $userRoles = auth()->user()->getRoleNames();
        $roleRoutes = [
            UserRole::ADMIN->value => 'dashboard',
            UserRole::EMPLOYEE->value => 'dashboard',
        ];

        foreach ($roleRoutes as $role => $route) {
            if ($userRoles->contains($role)) {
                return redirect()->route($route);
            }
        }

        abort(403, __('Unauthorized'));
    }

    public function dashboard(
        GetLowStockProductsCount $getLowStockProductsCount,
        GetProductsAboutToExpireCount $getProductsAboutToExpireCount,
        CalculateDailySalesTrendAction $calculateDailySalesTrendAction
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

        $aboutToExpire = Cache::remember('about_to_expire_count', now()->addDay(), function () use ($getProductsAboutToExpireCount) {
            return $getProductsAboutToExpireCount->execute();
        });

        $salesStats = Cache::remember('today_sales_stats', now()->addMinutes(10), function () use ($calculateDailySalesTrendAction) {
            return $calculateDailySalesTrendAction->execute();
        });

        return view('dashboard', array_merge([
            'aboutToExpire' => $aboutToExpire,
            'totalMinStockProducts' => $totalMinStockProducts,
        ], $salesStats));
    }

    public function sales()
    {
        return view('pages.sales');
    }
}
