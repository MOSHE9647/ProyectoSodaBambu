<?php

namespace App\Http\Controllers;

use App\Actions\Sale\UpsertSaleAction;
use App\Enums\EmployeeStatus;
use App\Enums\UserRole;
use App\Http\Requests\SaleRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Spatie\Permission\Middleware\RoleMiddleware;
use Yajra\DataTables\Facades\DataTables;

class SaleController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        $allowedRoles = [UserRole::ADMIN->value, UserRole::EMPLOYEE->value];

        return [
            new Middleware(RoleMiddleware::using($allowedRoles)),
            new Middleware(RoleMiddleware::using(UserRole::ADMIN->value), only: ['destroy']),
        ];
    }

    public function sales(Request $request)
    {
        $products = $this->getProductsList($request);
        $categories = Category::all();

        if ($request->ajax()) {
            return view('pages.sales._products-list', compact('products'))->render();
        }

        $lastSale = Sale::with('saleDetails', 'payments')->latest()?->first();

        return view('pages.sales.sales', compact('products', 'categories', 'lastSale'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = Sale::query()
                ->when(
                    $request->filled('user') && $request->user !== 'all', 
                    fn ($q) => $q->whereHas('user', fn ($q) => $q->where('id', $request->user))
                )
                ->when(
                    $request->filled('date') && $request->date !== null, 
                    fn ($q) => $q->whereDate('date', $request->date)
                )
                ->with('user')
                ->orderBy('date', 'desc');

            return DataTables::of($query)->toJson();
        }

        $users = User::whereDoesntHave('employee')
            ->orWhereHas('employee', function ($query) {
                $query->where('status', '!=', EmployeeStatus::INACTIVE->value);
            })
            ->get(['id', 'name']);

        return view('pages.sales.history.index', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaleRequest $saleStoreRequest, UpsertSaleAction $upsertSaleAction)
    {
        // Validates the request and retrieves the validated data
        $saleValidatedData = $saleStoreRequest->validated();

        // Separate the main sale data from the details and payment data
        $saleData = Arr::except($saleValidatedData, ['sale_details', 'payment_details']);
        $saleDetailsData = $saleValidatedData['sale_details'] ?? [];
        $salePaymentData = $saleValidatedData['payment_details'] ?? null;

        // Use the UpsertSaleAction to handle the creation/updating of the sale, its details, and payment
        $sale = $upsertSaleAction->execute(
            $saleData,
            $saleDetailsData,
            $salePaymentData
        );

        return response()->json([
            'message' => 'Venta registrada exitosamente.',
            'data' => $sale->load('saleDetails.product', 'payments'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        $sale->load('user', 'saleDetails.product', 'payments');

        return view('pages.sales.history.show', compact('sale'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaleRequest $saleStoreRequest, UpsertSaleAction $upsertSaleAction)
    {
        // Validates the request and retrieves the validated data
        $saleValidatedData = $saleStoreRequest->validated();

        // Separate the main sale data from the details and payment data
        $saleData = Arr::except($saleValidatedData, ['sale_details', 'payment_details']);
        $saleDetailsData = $saleValidatedData['sale_details'] ?? [];
        $salePaymentData = $saleValidatedData['payment_details'] ?? null;

        // Use the UpsertSaleAction to handle the creation/updating of the sale, its details, and payment
        $sale = $upsertSaleAction->execute(
            $saleData,
            $saleDetailsData,
            $salePaymentData
        );

        return response()->json([
            'message' => 'Venta actualizada exitosamente.',
            'data' => $sale->load('saleDetails', 'payments'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        $sale->delete();

        return redirect()->route('history.index')->with('success', 'Venta eliminada exitosamente.');
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
