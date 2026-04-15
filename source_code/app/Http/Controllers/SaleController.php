<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\RoleMiddleware;

class SaleController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        $allowedRoles = [UserRole::ADMIN->value, UserRole::EMPLOYEE->value];

        return [
            new Middleware(RoleMiddleware::using($allowedRoles)),
        ];
    }

    public function sales(Request $request)
    {
        $products = $this->getProductsList($request);
        $categories = Category::all();

        if ($request->ajax()) {
            return view('pages.sales._products-list', compact('products'))->render();
        }

        return view('pages.sales.sales', compact('products', 'categories'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sale $sale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        //
    }

    private function getProductsList(Request $request)
    {
        $search = $request->input('search', '');
        $categoryId = $request->input('category_id', '');

        $products = Product::query()
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%$search%"))
            ->when($categoryId, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->paginate(10);

        return $products;
    }
}
