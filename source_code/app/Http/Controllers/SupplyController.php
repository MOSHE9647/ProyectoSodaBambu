<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplyRequest; 
use App\Http\Resources\SupplyResource;
use App\Models\Supply;
use App\Enums\UserRole;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Throwable;
use Yajra\DataTables\DataTables;

class SupplyController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<int, \Illuminate\Routing\Controllers\Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('role:' . UserRole::ADMIN->value),
        ];
    }

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
        $query = Supply::query();

        // filter for supplies that are expiring soon (within the next 7 days)
        if ($request->boolean('expiring_soon') || $request->filter === 'expiring_soon') {
            $query->whereHas('purchaseDetails', function ($q) {
                $q->whereNotNull('expiration_date')
                  ->whereBetween('expiration_date', [
                      now()->startOfDay(), 
                      now()->addDays(7)->endOfDay()
                  ]);
            });
        }

        return DataTables::of($query)
            // aqui se le pasa la cantidad
            ->addColumn('quantity', function($supply) {
                $last = $supply->purchaseDetails()->latest()->first();
                return $last ? $last->quantity : 0;
            })
            // aqui se le pasa el precio
            ->addColumn('unit_price', function($supply) {
                $last = $supply->purchaseDetails()->latest()->first();
                return $last ? '₡' . number_format($last->unit_price, 2) : '₡0.00';
            })
            // aquis e le pasa al fecha de vencimento 
            ->addColumn('expiration_date', function($supply) {
                $last = $supply->purchaseDetails()->latest()->first();
                return ($last && $last->expiration_date) 
                    ? \Carbon\Carbon::parse($last->expiration_date)->format('d/m/Y') 
                    : 'N/A';
            })
            ->toJson();
    }

    return view('models.supplies.index');
}

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View|\Illuminate\View\View
     */
    public function create()
    {
        return view('models.supplies.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * 
     *
     * @param SupplyRequest $request
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(SupplyRequest $request)
    {
        $supplyData = $request->validated();
        $supply = Supply::withTrashed()->where('name', $supplyData['name'])->first();
        $message = 'Insumo creado correctamente.';

        if ($supply?->trashed()) {
            $supply->restore();
            $supply->update($supplyData);
            $message = 'Insumo restaurado y actualizado correctamente.';
        } else {
            Supply::create($supplyData);
        }

        return redirect()->route('supplies.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
     * @param Supply $supply
     * @return Factory|View|\Illuminate\View\View
     */
    public function show(Supply $supply)
    {
        $resource = SupplyResource::make($supply);
        return view('models.supplies.show', ['supply' => $resource]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Supply $supply
     * @return Factory|View|\Illuminate\View\View
     */
    public function edit(Supply $supply)
    {
        return view('models.supplies.edit', compact('supply'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param SupplyRequest $request
     * @param Supply $supply
     * @return RedirectResponse
     * @throws Throwable
     */
    public function update(SupplyRequest $request, Supply $supply)
    {
        $supplyData = $request->validated();
        $supply->update($supplyData);

        return redirect()->route('supplies.index')->with('success', 'Insumo actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Supply $supply
     * @return RedirectResponse
     * @throws Throwable
     */
    public function destroy(Supply $supply)
    {
        $supply->delete();

        return redirect()->back()->with('success', 'Insumo eliminado correctamente.');
    }
}