<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\UpsertPurchaseAction;
use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Supply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Arr;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response as HttpStatus;
use Yajra\DataTables\DataTables;

class PurchaseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        $allowedViewerRoles = UserRole::ADMIN->value.'|'.UserRole::EMPLOYEE->value;

        return [
            new Middleware(RoleMiddleware::using($allowedViewerRoles)),
            new Middleware(
                RoleMiddleware::using(UserRole::ADMIN->value),
                only: ['create', 'store', 'edit', 'update', 'destroy']
            ),
        ];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            // Supplier analytical report flow
            if ($request->has(['report', 'supplier_id'])) {
                return $this->getSupplierReport($request->input('supplier_id'));
            }

            // Standard list query with optimized eager loaded relations
            $query = Purchase::with(['supplier:id,name', 'user:id,name']);

            return DataTables::of($query)->toJson();
        }

        $suppliers = Supplier::all(['id', 'name']);

        return view('models.purchases.index', compact('suppliers'));
    }

    private function getSupplierReport(int $supplierId): JsonResponse
    {
        $items = PurchaseDetail::whereHas('purchase', fn ($q) => $q->where('supplier_id', $supplierId))
            ->with('purchasable:id,name')
            ->get()
            ->groupBy(fn ($d) => $d->purchasable_type.'|'.$d->purchasable_id)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'type' => (optional($first)->purchasable_type === Product::class) ? 'Producto' : 'Insumo',
                    'name' => $first->purchasable->name ?? 'N/A',
                    'times' => $group->count(),
                ];
            })
            ->values();

        return response()->json($items, HttpStatus::HTTP_OK);
    }

    public function create()
    {
        $suppliers = Supplier::all(['id', 'name']);
        $products = Product::whereIn('type', [ProductType::MERCHANDISE, ProductType::PACKAGED])
            ->get(['id', 'name', 'sale_price', 'reference_cost', 'type']);
        $supplies = Supply::all(['id', 'name', 'measure_unit', 'unit_price']);

        return view('models.purchases.create', compact('suppliers', 'products', 'supplies'));
    }

    public function store(PurchaseRequest $purchaseRequest, UpsertPurchaseAction $upsertPurchaseAction)
    {
        $validatedData = $purchaseRequest->validated();

        $purchaseData = Arr::except($validatedData, ['purchase_details', 'payment_details']);
        $purchaseDetailsData = $validatedData['purchase_details'] ?? [];
        $purchasePaymentData = $validatedData['payment_details'] ?? null;

        $purchase = $upsertPurchaseAction->execute(
            $purchaseData,
            $purchaseDetailsData,
            $purchasePaymentData
        );

        session()->flash('success', 'Compra registrada exitosamente.');

        return response()->json([
            'redirect' => route('purchases.index'),
            'message' => 'Datos de compra validados correctamente.',
            'data' => $purchase,
        ], HttpStatus::HTTP_CREATED);
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'details.purchasable');

        return view('models.purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load('details.purchasable');
        $suppliers = Supplier::all(['id', 'name']);
        $products = Product::whereIn('type', [ProductType::MERCHANDISE, ProductType::PACKAGED])
            ->get(['id', 'name', 'sale_price', 'reference_cost', 'type']);
        $supplies = Supply::all(['id', 'name', 'measure_unit', 'unit_price']);

        return view('models.purchases.edit', compact('purchase', 'suppliers', 'products', 'supplies'));
    }

    public function update(PurchaseRequest $purchaseRequest, UpsertPurchaseAction $upsertPurchaseAction)
    {
        $validatedData = $purchaseRequest->validated();

        $purchaseData = Arr::except($validatedData, ['purchase_details', 'payment_details']);
        $purchaseDetailsData = $validatedData['purchase_details'] ?? [];
        $purchasePaymentData = $validatedData['payment_details'] ?? null;

        $purchase = $upsertPurchaseAction->execute(
            $purchaseData,
            $purchaseDetailsData,
            $purchasePaymentData
        );

        session()->flash('success', 'Compra actualizada exitosamente.');

        return response()->json([
            'redirect' => route('purchases.index'),
            'message' => 'Datos de compra validados correctamente.',
            'data' => $purchase,
        ], HttpStatus::HTTP_OK);
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', 'Compra eliminada correctamente.');
    }
}
