<?php

namespace App\Http\Controllers;

use App\Actions\Sale\UpsertSaleAction;
use App\Enums\UserRole;
use App\Http\Requests\SaleRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Spatie\Permission\Middleware\RoleMiddleware;
use Yajra\DataTables\DataTables;

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

        $lastSale = Sale::with('saleDetails', 'payments')->latest()?->first();

        return view('pages.sales.sales', compact('products', 'categories', 'lastSale'));
    }

    /**
     * Display a listing of the resource (Historial de Ventas).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            if ($request->filled('start_date')) {
                $startDateInput = Carbon::parse($request->input('start_date'));
                $now = Carbon::now()->endOfDay(); // Fin del día actual para permitir consultas de hoy

                if ($startDateInput->greaterThan($now)) {
                    return response()->json([
                        'message' => 'La fecha de inicio no puede ser mayor a la fecha actual.',
                    ], 422);
                }
            }

            $query = Sale::with(['payments']);

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                $query->whereBetween('date', [$startDate, $endDate]);
            }

            if ($request->filled('invoice_number')) {
                $query->where('invoice_number', 'like', '%'.$request->input('invoice_number').'%');
            }

            return DataTables::of($query)
                ->editColumn('date', function ($sale) {
                    return $sale->date ? $sale->date->toDateString() : 'N/A';
                })
                ->editColumn('total', function ($sale) {
                    return $sale->total;
                })
                ->addColumn('payment_status', function ($sale) {
                    return $sale->payments->isNotEmpty() ? 'paid' : 'pending';
                })
                ->addColumn('is_admin', function ($sale) {
                    return auth()->user()->hasRole(\App\Enums\UserRole::ADMIN->value);
                })
                ->rawColumns([])
                ->toJson();
                    }

        return view('pages.sales.index');
    }

    /**
     * Display the specified resource (HU 1: Ver información detallada).
     */
    public function show(Sale $sale): View
    {
        $sale->load(['saleDetails.product', 'payments']);
        return view('pages.sales.show', compact('sale'));
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
            'data' => $sale->load('saleDetails', 'payments'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        // Criterio de Aceptación: Validar estrictamente en backend que solo el admin pueda borrar
        if (! auth()->user()->hasRole(UserRole::ADMIN->value)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo los administradores pueden eliminar registros del historial.',
            ], 403);
        }

        try {
            // Eliminar la venta de forma segura
            $sale->delete();

            return response()->json([
                'success' => true,
                'message' => 'La venta ha sido eliminada del historial correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error interno al intentar eliminar la venta.',
            ], 500);
        }
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
