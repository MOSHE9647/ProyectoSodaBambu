<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

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
            $query = Supplier::query()->select(['id', 'name', 'phone', 'email', 'created_at', 'updated_at', 'deleted_at']);
            return DataTables::of($query)
                ->addColumn('actions', function (Supplier $supplier) {
                    $show = '<button class="btn btn-sm btn-info btn-show" data-id="'. $supplier->id .'"><i class="bi bi-eye"></i></button>';
                    $edit = '<a href="'. route('suppliers.edit', $supplier->id) .'" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>';
                    $delete = '<button class="btn btn-sm btn-danger btn-delete" data-id="'. $supplier->id .'"><i class="bi bi-trash"></i></button>';
                    return '<div class="d-flex gap-1">'.$show.$edit.$delete.'</div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
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
     * @param Request $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:suppliers,email',
        ]);

        $supplierData = $request->only(['name', 'phone', 'email']);
        Supplier::create($supplierData);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor creado exitosamente.');
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
     * @param Request $request
     * @param Supplier $supplier
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:suppliers,email,' . $supplier->id,
        ]);

        $supplierData = $request->only(['name', 'phone', 'email']);
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
