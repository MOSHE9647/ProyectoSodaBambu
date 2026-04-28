<?php

namespace App\Http\Controllers;

use Amp\Http\HttpStatus;
use App\Actions\Products\SaveProductAction;
use App\Enums\UserRole;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        $allowedViewerRoles = UserRole::ADMIN->value.'|'.UserRole::EMPLOYEE->value;

        return [
            new Middleware(RoleMiddleware::using($allowedViewerRoles)),
            new Middleware(RoleMiddleware::using(UserRole::ADMIN->value), only: ['edit', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::query()->with('category')->withStockDetails();

            if ($request->boolean('low_stock') || $request->filter === 'low_stock') {
                $query->lowStock();
            }

            if ($request->boolean('expiring_soon') || $request->filter === 'expiring_soon') {
                $query->expiringSoon();
            }

            return DataTables::of($query)
                ->filterColumn('current_stock', fn ($query, $keyword) => $query->whereRaw('CAST(ps.current_stock AS TEXT) LIKE ?', ["%{$keyword}%"]))
                ->filterColumn('ps.current_stock', fn ($query, $keyword) => $query->whereRaw('CAST(ps.current_stock AS TEXT) LIKE ?', ["%{$keyword}%"]))
                ->filterColumn('minimum_stock', fn ($query, $keyword) => $query->whereRaw('CAST(ps.minimum_stock AS TEXT) LIKE ?', ["%{$keyword}%"]))
                ->filterColumn('ps.minimum_stock', fn ($query, $keyword) => $query->whereRaw('CAST(ps.minimum_stock AS TEXT) LIKE ?', ["%{$keyword}%"]))
                ->orderColumn('current_stock', 'ps.current_stock $1')
                ->orderColumn('ps.current_stock', 'ps.current_stock $1')
                ->orderColumn('minimum_stock', 'ps.minimum_stock $1')
                ->orderColumn('ps.minimum_stock', 'ps.minimum_stock $1')
                ->addColumn('expiration_days', function ($product): string {
                    if (! $product->expiration_date) {
                        return 'N/A';
                    }

                    $expirationDate = Carbon::parse($product->expiration_date)->startOfDay();
                    $daysRemaining = now()->startOfDay()->diffInDays($expirationDate, false);

                    if ($daysRemaining < 0) {
                        return 'Vencido';
                    }
                    if ($daysRemaining === 0) {
                        return 'Hoy';
                    }

                    return $daysRemaining.' día(s)';
                })
                ->toJson();
        }

        $lowStockProducts = ProductStock::query()
            ->with(['product:id,name,barcode,has_inventory'])
            ->whereHas('product', fn ($q) => $q->where('has_inventory', true))
            ->lowStock()
            ->orderByRaw('(minimum_stock - current_stock) DESC')
            ->limit(5)
            ->get();

        // Aplicamos nuestro nuevo scope aquí también
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
        $productStock = null;

        return view('models.products.create', compact('categories', 'productStock'));
    }

    public function store(ProductRequest $request, SaveProductAction $action)
    {
        // Delegamos todo al Action. Extraemos el mensaje de la posición 1 del array devuelto.
        $payload = $request->validated();
        [$product, $message] = $action->execute($payload);

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
        $payload = $request->validated();
        // Reutilizamos el Action enviándole el producto actual
        [$product, $message] = $action->execute($payload, $product);

        return redirect()->route('products.index')->with('success', $message);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
