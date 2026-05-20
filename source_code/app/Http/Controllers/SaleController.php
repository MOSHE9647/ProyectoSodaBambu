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

        $lastSale = Sale::with('saleDetails', 'payments')->latest()?->first();

        return view('pages.sales.sales', compact('products', 'categories', 'lastSale'));
    }

    /**
     * Display a listing of the resource (Historial de Ventas).
     */
    public function index(\Illuminate\Http\Request $request)
    {
        if ($request->ajax()) {
            // Consulta base cargando las relaciones necesarias para el modal y los tiquetes
            $query = \App\Models\Sale::with(['saleDetails.product', 'payments']);

            // 1. Filtro personalizado por rango de fechas (Criterio HU)
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                $endDate = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                $query->whereBetween('date', [$startDate, $endDate]);
            }

            // 2. Filtro personalizado por número de factura (Criterio HU)
            if ($request->filled('invoice_number')) {
                $query->where('invoice_number', 'like', '%' . $request->input('invoice_number') . '%');
            }

            return \Yajra\DataTables\DataTables::of($query)
                ->editColumn('date', function ($sale) {
                    return $sale->date ? $sale->date->format('d/m/Y h:i A') : 'N/A';
                })
                ->editColumn('total', function ($sale) {
                    return '₡ ' . number_format($sale->total, 0);
                })
                ->addColumn('payment_status', function ($sale) {
                    // Renderizar un badge estético según si tiene pagos asociados
                    return $sale->payments->isNotEmpty() 
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Pagado</span>'
                        : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2">Pendiente</span>';
                })
                ->addColumn('actions', function ($sale) {
                    // Validar si el usuario autenticado tiene rol de Administrador
                    // Nota: Si en tu sistema usan un método diferente como $user->isAdmin(), lo adaptas.
                    $isAdmin = auth()->user()->hasRole(\App\Enums\UserRole::ADMIN->value);

                    $btnVer = '<button type="button" class="btn btn-info btn-sm btn-view-sale me-1 text-white" data-id="' . $sale->id . '" title="Ver Información"><i class="bi bi-eye"></i></button>';
                    $btnPrint = '<button type="button" class="btn btn-primary btn-sm btn-print-sale me-1" data-id="' . $sale->id . '" title="Reimprimir Tiquete"><i class="bi bi-printer"></i></button>';
                    
                    // Si es admin se activa el botón de borrar, si no, se muestra deshabilitado gris
                    $btnDelete = $isAdmin 
                        ? '<button type="button" class="btn btn-danger btn-sm btn-delete-sale" data-id="' . $sale->id . '" title="Eliminar"><i class="bi bi-trash"></i></button>'
                        : '<button type="button" class="btn btn-secondary btn-sm opacity-50" disabled title="Solo administradores pueden eliminar"><i class="bi bi-trash"></i></button>';

                    return '<div class="d-flex justify-content-start align-items-center">' . $btnVer . $btnPrint . $btnDelete . '</div>';
                })
                ->rawColumns(['payment_status', 'actions'])
                ->toJson();
        }

        // Si no es un llamado AJAX, simplemente renderiza la vista que creamos en el paso anterior
        return view('pages.sales.index');
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
    public function destroy(\App\Models\Sale $sale)
    {
        // Criterio de Aceptación: Validar estrictamente en backend que solo el admin pueda borrar
        if (!auth()->user()->hasRole(\App\Enums\UserRole::ADMIN->value)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo los administradores pueden eliminar registros del historial.'
            ], 403);
        }

        try {
            // Eliminar la venta de forma segura
            $sale->delete();

            return response()->json([
                'success' => true,
                'message' => 'La venta ha sido eliminada del historial correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error interno al intentar eliminar la venta.'
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
