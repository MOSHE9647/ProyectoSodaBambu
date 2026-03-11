<?php

namespace App\Http\Controllers;

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

	public function dashboard()
	{	

		/**
		 * Retrieves the count of products with stock levels at or below their minimum threshold.
		 * 
		 * The result is cached indefinitely to improve performance on subsequent requests.
		 * The cache key is 'low_stock_count' and will persist until manually cleared.
		 */
		$totalMinStockProducts = Cache::rememberForever('low_stock_count', function () {
        return \App\Models\ProductStock::whereRaw('current_stock <= minimum_stock')->count();
    	});


		$aboutToExpire = random_int(0, 10);
		return view('dashboard', compact('aboutToExpire', 'totalMinStockProducts'));
	}

	public function sales()
	{
		return view('pages.sales');
	}
}
