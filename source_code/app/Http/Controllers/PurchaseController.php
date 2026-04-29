<?php

namespace App\Http\Controllers;

use Amp\Http\HttpStatus;
use App\Actions\Inventory\UpsertPurchaseAction;
use App\Enums\ProductType;
use App\Http\Requests\PurchaseRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Supply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {

            // Reporte: productos/insumos suministrados por un proveedor
            if ($request->has('report') && $request->has('supplier_id')) {
                $supplier = Supplier::findOrFail($request->supplier_id);

                $items = PurchaseDetail::query()
                    ->whereHas('purchase', fn ($q) => $q->where('supplier_id', $supplier->id))
                    ->with('purchasable')
                    ->get()
                    ->groupBy(fn ($d) => $d->purchasable_type.'|'.$d->purchasable_id)
                    ->map(function ($group) {
                        $first = $group->first();

                        return [
                            'type' => class_basename($first->purchasable_type) === 'Product' ? 'Producto' : 'Insumo',
                            'name' => $first->purchasable->name ?? 'N/A',
                            'times' => $group->count(),
                        ];
                    })
                    ->values();

                return response()->json([
                    'supplier' => $supplier->name,
                    'items' => $items,
                ]);
            }

            // EIF-161: DataTable con paginación correcta usando offset/limit explícitos
            $columns = [
                0 => 'id',
                1 => 'invoice_number',
                2 => 'supplier_id',
                3 => 'date',
                4 => 'total',
                5 => 'payment_status',
            ];

            $query = Purchase::with('supplier:id,name');

            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%$search%")
                        ->orWhere('date', 'like', "%$search%")
                        ->orWhere('total', 'like', "%$search%")
                        ->orWhere('payment_status', 'like', "%$search%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%$search%");
                        });
                });
            }

            // EIF-161: Ordenamiento seguro con validación de índice de columna
            if ($request->has('order') && isset($request->order[0]['column'])) {
                $colIndex = (int) $request->order[0]['column'];
                $orderDir = $request->order[0]['dir'] === 'desc' ? 'desc' : 'asc';
                $orderCol = $columns[$colIndex] ?? 'id';

                if ($orderCol === 'supplier_id') {
                    $query->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                        ->orderBy('suppliers.name', $orderDir)
                        ->select('purchases.*');
                } else {
                    $query->orderBy('purchases.'.$orderCol, $orderDir);
                }
            } else {
                $query->orderBy('purchases.id', 'desc');
            }

            $recordsTotal = Purchase::count();
            $recordsFiltered = $query->count();

            // EIF-161: Respetar length=-1 (mostrar todo) y aplicar offset/limit correctamente
            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);

            if ($length !== -1) {
                $query->offset($start)->limit($length);
            }

            $purchases = $query->get();

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $purchases->map(function ($purchase) {
                    return [
                        'id' => $purchase->id,
                        'invoice_number' => $purchase->invoice_number,
                        'supplier_id' => $purchase->supplier_id,
                        'supplier' => ['name' => $purchase->supplier->name ?? 'N/A'],
                        'date' => $purchase->date->format('Y-m-d'),
                        'total' => $purchase->total,
                        'payment_status' => $purchase->payment_status->value,
                    ];
                }),
            ]);
        }

        return view('models.purchases.index');
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

        $upsertPurchaseAction->execute(
            $purchaseData,
            $purchaseDetailsData,
            $purchasePaymentData
        );

        session()->flash('success', 'Compra registrada exitosamente.');

        return response()->json([
            'redirect' => route('purchases.index'),
            'message' => 'Datos de compra validados correctamente.',
        ], HttpStatus::CREATED);
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

        $upsertPurchaseAction->execute(
            $purchaseData,
            $purchaseDetailsData,
            $purchasePaymentData
        );

        session()->flash('success', 'Compra actualizada exitosamente.');

        return response()->json([
            'redirect' => route('purchases.index'),
            'message' => 'Datos de compra validados correctamente.',
        ], HttpStatus::OK);
    }

    public function getOffcanvasForm(string $type): JsonResponse|string
    {
        return match ($type) {
            'category' => view('models.purchases.offcanvas.modals._category')->render(),
            'supplier' => view('models.purchases.offcanvas._supplier')->render(),
            'product' => view('models.products.form', [
                'categories' => Category::all(['id', 'name']),
                'isOffcanvas' => true,
                'product' => null,
            ])->render(),
            'supply' => view('models.supplies._form', [
                'isOffcanvas' => true,
                'supply' => null,
            ])->render(),
            default => response()->json(['error' => 'Tipo de formulario no válido.'], HttpStatus::BAD_REQUEST)
        };
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return redirect()->route('purchases.index')->with('success', 'Compra eliminada correctamente.');
    }
}
