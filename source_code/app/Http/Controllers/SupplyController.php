<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\SupplyRequest;
use App\Http\Resources\SupplyResource;
use App\Models\Supply;
use Carbon\Carbon;
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
    public static function middleware(): array
    {
        return [
            new Middleware('role:' . UserRole::ADMIN->value),
        ];
    }

    /**
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Supply::query();

            if ($request->boolean('expiring_soon') || $request->filter === 'expiring_soon') {
                $query->whereHas('purchaseDetails', function ($q) {
                    $q->whereNotNull('expiration_date')
                        ->whereBetween('expiration_date', [
                            now()->startOfDay(),
                            now()->addDays(7)->endOfDay(),
                        ]);
                });
            }

            return DataTables::of($query)
                ->addColumn('quantity', function ($supply) {
                    $last = $supply->purchaseDetails()->latest()->first();
                    return $last ? $last->quantity : 0;
                })
                ->addColumn('unit_price', function ($supply) {
                    $last = $supply->purchaseDetails()->latest()->first();
                    return $last ? '₡' . number_format($last->unit_price, 2) : '₡0.00';
                })
                ->addColumn('expiration_date', function ($supply) {
                    $last = $supply->purchaseDetails()->latest()->first();
                    return ($last && $last->expiration_date)
                        ? Carbon::parse($last->expiration_date)->format('d/m/Y')
                        : 'N/A';
                })
                ->toJson();
        }

        return view('models.supplies.index');
    }

    public function create()
    {
        return view('models.supplies.create');
    }

    /**
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
        } elseif ($supply) {
            
            return redirect()->route('supplies.create')
                ->withErrors(['name' => 'Ya existe un insumo activo con este nombre.'])
                ->withInput();
        } else {
            $supply = Supply::create($supplyData);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'supply' => [
                    'id'   => $supply->id,
                    'name' => $supply->name,
                    'measure_unit' => $supply->measure_unit,
                ],
            ]);
        }

        return redirect()->route('supplies.index')->with('success', $message);
    }

    public function show(Supply $supply)
    {
        $resource = SupplyResource::make($supply);
        return view('models.supplies.show', ['supply' => $resource]);
    }

    public function edit(Supply $supply)
    {
        return view('models.supplies.edit', compact('supply'));
    }

    /**
     * @throws Throwable
     */
    public function update(SupplyRequest $request, Supply $supply)
    {
        $supplyData = $request->validated();
        $supply->update($supplyData);
        return redirect()->route('supplies.index')->with('success', 'Insumo actualizado correctamente.');
    }

    /**
     * @throws Throwable
     */
    public function destroy(Supply $supply)
    {
        $supply->delete();
        return redirect()->back()->with('success', 'Insumo eliminado correctamente.');
    }
}