<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            // Generar datos falsos para DataTable
            $purchases = collect();
            for ($i = 0; $i < 20; $i++) {
                $purchase = Purchase::factory()->make();
                $purchase->setRelation('supplier', Supplier::factory()->make());
                $purchase->id = $i + 1;
                $purchases->push($purchase);
            }

            $perPage = $request->input('length', 10);
            $start = $request->input('start', 0);
            $paginated = $purchases->slice($start, $perPage)->values();

            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => $purchases->count(),
                'recordsFiltered' => $purchases->count(),
                'data' => $paginated->map(function ($purchase) {
                    return [
                        'id' => $purchase->id,
                        'invoice_number' => $purchase->invoice_number,
                        'supplier' => [
                            'name' => $purchase->supplier->name
                        ],
                        'date' => $purchase->date->format('Y-m-d'),
                        'total' => $purchase->total,
                        'payment_status' => $purchase->payment_status->value ?? $purchase->payment_status,
                    ];
                }),
            ]);
        }

        return view('models.purchases.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Generar proveedores falsos para el select
        $suppliers = Supplier::factory()->count(5)->make();
        foreach ($suppliers as $index => $supplier) {
            $supplier->id = $index + 1;
        }
        return view('models.purchases.create', compact('suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Simulación de guardado
        return redirect()->route('purchases.index')->with('success', 'Compra creada correctamente (simulado).');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Purchase $purchase)
    {
        // Si el modelo no existe (porque no hay BD), crear uno falso
        if (!$purchase->exists) {
            $purchase = Purchase::factory()->make();
            $purchase->setRelation('supplier', Supplier::factory()->make());
            $purchase->id = 1;
        }

        // Si es una petición AJAX (desde el modal), retornar solo la vista parcial
        if ($request->ajax()) {
            return view('models.purchases._show_partial', compact('purchase'));
        }

        return view('models.purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        $suppliers = Supplier::factory()->count(5)->make();
        foreach ($suppliers as $index => $supplier) {
            $supplier->id = $index + 1;
        }

        if (!$purchase->exists) {
            $purchase = Purchase::factory()->make();
            $purchase->setRelation('supplier', Supplier::factory()->make());
            $purchase->id = 1;
        }

        return view('models.purchases.edit', compact('purchase', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        // Simulación de actualización
        return redirect()->route('purchases.index')->with('success', 'Compra actualizada correctamente (simulado).');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        // Simulación de eliminación
        return redirect()->route('purchases.index')->with('success', 'Compra eliminada correctamente (simulado).');
    }
}