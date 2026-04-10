<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use Exception;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Middleware\RoleMiddleware;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller implements HasMiddleware
{
    /**
     * Define middleware for the controller.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        $allowedViewerRoles = UserRole::ADMIN->value.'|'.UserRole::EMPLOYEE->value;

        return [
            new Middleware(RoleMiddleware::using($allowedViewerRoles)),
            new Middleware(RoleMiddleware::using(UserRole::ADMIN->value), only: ['edit', 'update', 'destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return Factory|View|JsonResponse|\Illuminate\View\View
     *
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Product::query()
                ->with('category')
                ->leftJoin('product_stocks as ps', function ($join): void {
                    $join
                        ->on('ps.product_id', '=', 'products.id')
                        ->whereNull('ps.deleted_at');
                })
                ->select([
                    'products.*',
                    'ps.current_stock',
                    'ps.minimum_stock',
                ]);

            if ($request->boolean('low_stock') || $request->filter === 'low_stock') {
                $query
                    ->where('has_inventory', true)
                    ->whereNotNull('ps.current_stock')
                    ->whereColumn('ps.current_stock', '<=', 'ps.minimum_stock');
            }

            if ($request->boolean('expiring_soon') || $request->filter === 'expiring_soon') {
                $query
                    ->whereNotNull('expiration_date')
                    ->whereRaw('DATEDIFF(expiration_date, CURDATE()) BETWEEN 0 AND expiration_alert_days');
            }

            return DataTables::of($query)
                ->filterColumn('current_stock', function ($query, $keyword): void {
                    $query->whereRaw('CAST(ps.current_stock AS TEXT) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('ps.current_stock', function ($query, $keyword): void {
                    $query->whereRaw('CAST(ps.current_stock AS TEXT) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('minimum_stock', function ($query, $keyword): void {
                    $query->whereRaw('CAST(ps.minimum_stock AS TEXT) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('ps.minimum_stock', function ($query, $keyword): void {
                    $query->whereRaw('CAST(ps.minimum_stock AS TEXT) LIKE ?', ["%{$keyword}%"]);
                })
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
            ->whereHas('product', function ($query): void {
                $query->where('has_inventory', true);
            })
            ->lowStock()
            ->orderByRaw('(minimum_stock - current_stock) DESC')
            ->limit(5)
            ->get();

        $expiringSoonProducts = Product::query()
            ->whereNotNull('expiration_date')
            ->whereRaw('DATEDIFF(expiration_date, CURDATE()) BETWEEN 0 AND expiration_alert_days')
            ->orderBy('expiration_date')
            ->limit(5)
            ->get();

        return view('models.products.index', compact('lowStockProducts', 'expiringSoonProducts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $productStock = null;

        return view('models.products.create', compact('categories', 'productStock'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function store(ProductRequest $request)
    {
        $payload = $request->validated();
        $stockData = $this->extractStockData($payload);
        $productData = $this->applyPricingRules($payload);

        $product = null;
        $message = 'Producto creado exitosamente.';

        DB::transaction(function () use ($productData, $stockData, &$product, &$message): void {
            $product = ! empty($productData['barcode'])
                ? Product::withTrashed()->where('barcode', $productData['barcode'])->first()
                : null;

            if ($product?->trashed()) {
                $product->restore();
                $product->update($productData);
                $message = 'Producto restaurado y actualizado exitosamente.';
            } else {
                $product = Product::create($productData);
            }

            $this->syncInventoryStock($product, $productData, $stockData);
        });

        return redirect()->route('products.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function show(Product $product)
    {
        $product->load(['category', 'stock']);

        return view('models.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $product->load('stock');
        $productStock = ProductStock::withTrashed()
            ->where('product_id', $product->id)
            ->first();

        return view('models.products.edit', compact('product', 'categories', 'productStock'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function update(ProductRequest $request, Product $product)
    {
        $payload = $request->validated();
        $stockData = $this->extractStockData($payload);
        $productData = $this->applyPricingRules($payload);

        DB::transaction(function () use ($product, $productData, $stockData): void {
            $product->update($productData);
            $this->syncInventoryStock($product, $productData, $stockData);
        });

        return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente.');
    }

    /**
     * Applies pricing business rules based on product type.
     *
     * @param  array<string, mixed>  $productData
     * @return array<string, mixed>
     */
    private function applyPricingRules(array $productData): array
    {
        $type = $productData['type'] ?? null;

        if ($type === ProductType::MERCHANDISE->value) {
            $productData['sale_price'] = Product::calculateSalePrice(
                (float) $productData['reference_cost'],
                (float) $productData['tax_percentage'],
                (float) $productData['margin_percentage'],
            );

            return $productData;
        }

        $productData['reference_cost'] = 0;
        $productData['tax_percentage'] = 0;
        $productData['margin_percentage'] = 0;
        $productData['expiration_date'] = null;
        $productData['expiration_alert_days'] = 7;

        $productData['sale_price'] = (float) ($productData['sale_price'] ?? 0);

        return $productData;
    }

    /**
     * Extract stock payload and remove it from product payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{minimum_stock:int|null}
     */
    private function extractStockData(array &$payload): array
    {
        $stockData = [
            'minimum_stock' => isset($payload['minimum_stock']) ? (int) $payload['minimum_stock'] : null,
        ];

        unset($payload['current_stock'], $payload['minimum_stock']);

        return $stockData;
    }

    /**
     * Sync relational stock data when inventory is enabled.
     *
     * @param  array<string, mixed>  $productData
     * @param  array{minimum_stock:int|null}  $stockData
     */
    private function syncInventoryStock(Product $product, array $productData, array $stockData): void
    {
        if (! (bool) ($productData['has_inventory'] ?? false)) {
            return;
        }

        $stock = ProductStock::withTrashed()->firstOrNew([
            'product_id' => $product->id,
        ]);

        $wasTrashed = $stock->exists && $stock->trashed();
        $isNewStock = ! $stock->exists;

        $stock->fill([
            'current_stock' => $isNewStock
                ? $this->getDefaultCurrentStockFromSeederLogic()
                : (int) ($stock->current_stock ?? 0),
            'minimum_stock' => $stockData['minimum_stock'] ?? ($isNewStock ? 15 : (int) ($stock->minimum_stock ?? 15)),
        ]);
        $stock->save();

        if ($wasTrashed) {
            $stock->restore();
        }
    }

    /**
     * Reuse ProductStockSeeder baseline for initial current stock.
     */
    private function getDefaultCurrentStockFromSeederLogic(): int
    {
        return rand(20, 100);
    }
}
