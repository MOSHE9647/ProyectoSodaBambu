<?php

namespace App\Http\Controllers;

use App\Actions\Sale\UpsertSaleAction;
use App\Enums\UserRole;
use App\Http\Requests\SaleRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
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

        $lastSale = Sale::with('details', 'payments')->latest()?->first();

        return view('pages.sales.sales', compact('products', 'categories', 'lastSale'));
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
            'data' => $sale->load('details', 'payments'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        //
    }

    public function showPaymentModal(float $paymentTotal): string
    {
        $validatedData = Validator::make(
            ['total' => $paymentTotal],
            ['total' => ['required', 'numeric', 'min:0']],
            [
                'total.required' => 'El total de la venta es requerido para procesar el pago.',
                'total.numeric' => 'El total de la venta debe ser un número válido.',
                'total.min' => 'El total de la venta no puede ser negativo.',
            ]
        )->validate();

        return view('pages.sales._payment-modal', [
            'paymentTotal' => (float) $validatedData['total'],
        ])->render();
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
            'data' => $sale->load('details', 'payments'),
        ], 200);
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
