<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\SupplyRequest;
use App\Http\Resources\SupplyResource;
use App\Models\Supply;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Throwable;
use Yajra\DataTables\DataTables;

class SupplyController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        $allowedViewerRoles = UserRole::ADMIN->value.'|'.UserRole::EMPLOYEE->value;

        return [
            new Middleware(RoleMiddleware::using($allowedViewerRoles)),
            new Middleware(
                RoleMiddleware::using(UserRole::ADMIN->value),
                only: ['edit', 'update', 'destroy']
            ),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return Factory|View|JsonResponse|\Illuminate\View\View
     *
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Supply::query();

            return DataTables::of($query)
                ->editColumn('quantity', function ($supply) {
                    return $supply->quantity ?? 0;
                })
                ->editColumn('unit_price', function ($supply) {
                    return $supply->unit_price ? '₡'.number_format($supply->unit_price, 2) : '₡0.00';
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
     * @return RedirectResponse
     *
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
            $supply = Supply::create($supplyData);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'supply' => [
                    'id' => $supply->id,
                    'name' => $supply->name,
                    'unit_price' => (float) ($supply->unit_price ?? 0),
                ],
            ]);
        }

        return redirect()->route('supplies.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     *
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
     * @return Factory|View|\Illuminate\View\View
     */
    public function edit(Supply $supply)
    {
        return view('models.supplies.edit', compact('supply'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return RedirectResponse
     *
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
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function destroy(Supply $supply)
    {
        $supply->delete();

        return redirect()->back()->with('success', 'Insumo eliminado correctamente.');
    }
}
