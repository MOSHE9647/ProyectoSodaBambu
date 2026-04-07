<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\GetLowStockProductsCount;
use App\Actions\Inventory\GetProductsAboutToExpireCount;
use App\Actions\Sale\GetTodaySalesTotal;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Cache;
use App\Models\ProductStock;

class HomeController extends Controller
{
	public function index()
	{
		if (!auth()->check()) {
			return redirect()->route('login');
		}

		$userRoles = auth()->user()->getRoleNames();
		$roleRoutes = [
			UserRole::ADMIN->value => 'dashboard',
			UserRole::EMPLOYEE->value => 'sales',
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
		GetTodaySalesTotal $getTodaySalesTotal
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

		$todaySalesTotal = Cache::remember('today_sales_total', now()->addDay(), function () use ($getTodaySalesTotal) {
			return $getTodaySalesTotal->execute();
		});

		$yesterdaySalesTotal = Cache::remember('yesterday_sales_at_this_time', now()->addMinutes(10), function () use ($getTodaySalesTotal) {
			return $getTodaySalesTotal->execute(now()->subDay()->toDateString(), true);
		});

		// Lógica adaptada
		if ($yesterdaySalesTotal > 0) {
			$percentage = (($todaySalesTotal - $yesterdaySalesTotal) / $yesterdaySalesTotal) * 100;
		} else {
			// Si ayer hubo 0, y hoy > 0, es 100% crecimiento. Si ambos son 0, es 0%.
			$percentage = $todaySalesTotal > 0 ? 100 : 0;
		}

		$trendDirection = $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'neutral');
		$trendSign = $percentage > 0 ? '+' : '';
		$salesTrendText = $trendSign . round($percentage) . '%';

		return view('dashboard', compact(
			'aboutToExpire', 
			'totalMinStockProducts', 
			'todaySalesTotal', 
			'salesTrendText', 
			'trendDirection'
		));
	}

	public function sales()
	{
		return view('pages.sales');
	}
}
