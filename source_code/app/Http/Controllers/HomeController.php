<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ProductStock;
use App\Models\PurchaseDetail;
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
            return ProductStock::whereRaw('current_stock <= minimum_stock')->count();
        });

        $aboutToExpire = Cache::remember('about_to_expire_count', now()->addDay(), function () {
            return PurchaseDetail::countAboutToExpireByProductAlert();
        });

        return view('dashboard', compact('aboutToExpire', 'totalMinStockProducts'));
    }

    public function sales()
    {
        return view('pages.sales');
    }
}
