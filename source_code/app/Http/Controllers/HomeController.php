<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;

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
		$totalMinStockProducts = random_int(0, 20);
		$aboutToExpire = random_int(0, 10);
		return view('dashboard', compact('aboutToExpire', 'totalMinStockProducts'));
	}

	public function sales()
	{
		return view('pages.sales');
	}
}
