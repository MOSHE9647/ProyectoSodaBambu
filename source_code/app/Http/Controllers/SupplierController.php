<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $suppliers = Supplier::withTrashed()->select(['id', 'name', 'phone', 'email', 'created_at', 'deleted_at']);
            
            return DataTables::of($suppliers)
                ->addColumn('actions', function ($supplier) {
                    $showUrl = route('suppliers.show', $supplier->id);
                    $editUrl = route('suppliers.edit', $supplier->id);
                    $deleteUrl = route('suppliers.destroy', $supplier->id);
                    
                    return view('components.datatable-actions', [
                        'showUrl' => $showUrl,
                        'editUrl' => $editUrl,
                        'deleteUrl' => $deleteUrl,
                        'showTooltip' => 'Ver detalles',
                        'editTooltip' => 'Editar proveedor',
                        'deleteTooltip' => 'Eliminar proveedor'
                    ])->render();
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('Suppliers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('Suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:suppliers,email',
        ]);

        $supplier = Supplier::create($request->all());

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return view('Suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return view('Suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:suppliers,email,' . $supplier->id,
        ]);

        $supplier->update($request->all());

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor eliminado exitosamente.');
    }
}
