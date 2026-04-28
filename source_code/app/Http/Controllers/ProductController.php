<?php

namespace App\Http\Controllers;

use Amp\Http\HttpStatus;
use App\Actions\Products\SaveProductAction;
use App\Enums\UserRole;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(RoleMiddleware::using(UserRole::ADMIN->value.'|'.UserRole::EMPLOYEE->value)),
            new Middleware(RoleMiddleware::using(UserRole::ADMIN->value), only: ['edit', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filter = $request->input('filter');

            $query = Product::query()
                ->with('category')
                ->withStockDetails()
                ->leftJoin('product_stocks as ps', 'ps.product_id', '=', 'products.id')
                ->addSelect('products.*', 'ps.current_stock', 'ps.minimum_stock')
                ->when($request->boolean('low_stock') || $filter === 'low_stock', fn ($q) => $q->lowStock())
                ->when($request->boolean('expiring_soon') || $filter === 'expiring_soon', fn ($q) => $q->expiringSoon());

            return DataTables::of($query)
                ->filterColumn('current_stock', fn ($q, $keyword) => $q->whereRaw('CAST(ps.current_stock AS TEXT) LIKE ?', ["%{$keyword}%"]))
                ->filterColumn('minimum_stock', fn ($q, $keyword) => $q->whereRaw('CAST(ps.minimum_stock AS TEXT) LIKE ?', ["%{$keyword}%"]))
                ->orderColumn('current_stock', 'ps.current_stock $1')
                ->orderColumn('minimum_stock', 'ps.minimum_stock $1')
                ->addColumn('expiration_days', fn (Product $product) => $product->expiration_label)
                ->toJson();
        }

        $lowStockProducts = ProductStock::query()
            ->with(['product:id,name,barcode,has_inventory'])
            ->whereHas('product', fn ($q) => $q->where('has_inventory', true))
            ->lowStock()
            ->orderByRaw('(minimum_stock - current_stock) DESC')
            ->limit(5)
            ->get();

        $expiringSoonProducts = Product::query()
            ->expiringSoon()
            ->orderBy('expiration_date')
            ->limit(5)
            ->get();

        return view('models.products.index', compact('lowStockProducts', 'expiringSoonProducts'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('models.products.create', compact('categories'));
    }

    public function store(ProductRequest $request, SaveProductAction $action)
    {
        [$product, $message] = $action->execute($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'product' => ['id' => $product->id, 'name' => $product->name],
            ], HttpStatus::CREATED);
        }

        return redirect()->route('products.index')->with('success', $message);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'stock']);

        return view('models.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $product->load('stock');

        return view('models.products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product, SaveProductAction $action)
    {
        [, $message] = $action->execute($request->validated(), $product);

        return redirect()->route('products.index')->with('success', $message);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
