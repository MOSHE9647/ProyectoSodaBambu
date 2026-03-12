<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Factory|View|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Supplier::query())->toJson();
        }

        return view('models.suppliers.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function create()
    {
        return view('models.suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param SupplierRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(Request $request)
    {
        // Si la petición es AJAX y espera JSON (creación rápida desde offcanvas)
        if ($request->wantsJson()) {
            $validator = Validator::make($request->all(), [
                'name'  => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|unique:suppliers,email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

            $supplier = Supplier::create($request->all());

            return response()->json([
                'success'  => true,
                'supplier' => $supplier,
                'message'  => 'Proveedor creado correctamente.'
            ]);
        }

        // Código original para peticiones normales (no AJAX)
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|unique:suppliers,email',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param Supplier $supplier
     * @return Factory|View|\Illuminate\View\View
     */
    public function show(Supplier $supplier)
    {
        return view('models.suppliers.show', ['supplier' => $supplier]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Supplier $supplier
     * @return Factory|View|\Illuminate\View\View
     */
    public function edit(Supplier $supplier)
    {
        return view('models.suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param SupplierRequest $request
     * @param Supplier $supplier
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $supplierData = $request->validated();

        $supplier->update($supplierData);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Supplier $supplier
     * @return RedirectResponse
     * @throws Throwable
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Proveedor eliminado exitosamente.');
    }
}
