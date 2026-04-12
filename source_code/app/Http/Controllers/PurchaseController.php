<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Supply;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {

            // Reporte: productos/insumos suministrados por un proveedor
            if ($request->has('report') && $request->has('supplier_id')) {
                $supplier = Supplier::findOrFail($request->supplier_id);

                $items = \App\Models\PurchaseDetail::query()
                    ->whereHas('purchase', fn($q) => $q->where('supplier_id', $supplier->id))
                    ->with('purchasable')
                    ->get()
                    ->groupBy(fn($d) => $d->purchasable_type . '|' . $d->purchasable_id)
                    ->map(function ($group) {
                        $first = $group->first();
                        return [
                            'type'  => class_basename($first->purchasable_type) === 'Product' ? 'Producto' : 'Insumo',
                            'name'  => $first->purchasable->name ?? 'N/A',
                            'times' => $group->count(),
                        ];
                    })
                    ->values();

                return response()->json([
                    'supplier' => $supplier->name,
                    'items'    => $items,
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
                $colIndex  = (int) $request->order[0]['column'];
                $orderDir  = $request->order[0]['dir'] === 'desc' ? 'desc' : 'asc';
                $orderCol  = $columns[$colIndex] ?? 'id';

                if ($orderCol === 'supplier_id') {
                    $query->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                        ->orderBy('suppliers.name', $orderDir)
                        ->select('purchases.*');
                } else {
                    $query->orderBy('purchases.' . $orderCol, $orderDir);
                }
            } else {
                $query->orderBy('purchases.id', 'desc');
            }

            $recordsTotal    = Purchase::count();
            $recordsFiltered = $query->count();

            // EIF-161: Respetar length=-1 (mostrar todo) y aplicar offset/limit correctamente
            $start  = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);

            if ($length !== -1) {
                $query->offset($start)->limit($length);
            }

            $purchases = $query->get();

            return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $purchases->map(function ($purchase) {
                    return [
                        'id'             => $purchase->id,
                        'invoice_number' => $purchase->invoice_number,
                        'supplier_id'    => $purchase->supplier_id,
                        'supplier'       => ['name' => $purchase->supplier->name ?? 'N/A'],
                        'date'           => $purchase->date->format('Y-m-d'),
                        'total'          => $purchase->total,
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
        $products  = Product::all(['id', 'name', 'sale_price', 'type']);
        $supplies  = Supply::all(['id', 'name', 'measure_unit']);
        return view('models.purchases.create', compact('suppliers', 'products', 'supplies'));
    }

    public function store(Request $request)
    {
        $paymentValues = implode(',', array_column(\App\Enums\PaymentStatus::cases(), 'value'));

        $validated = $request->validate([
            'supplier_id'                => 'required|exists:suppliers,id',
            'invoice_number'             => 'required|string|max:255|unique:purchases,invoice_number',
            'date'                       => 'required|date',
            'payment_status'             => 'required|string|in:' . $paymentValues,
            'total'                      => 'required|numeric|min:0',
            'details'                    => 'required|array|min:1',
            'details.*.purchasable_type' => 'required|in:product,supply',
            'details.*.purchasable_id'   => 'required|integer',
            'details.*.subtotal'         => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $purchase = Purchase::create([
                'supplier_id'    => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'date'           => $validated['date'],
                'payment_status' => $validated['payment_status'],
                'total'          => $validated['total'],
            ]);

            foreach ($validated['details'] as $detail) {
                $modelClass  = $detail['purchasable_type'] === 'product' ? Product::class : Supply::class;
                $purchasable = $modelClass::findOrFail($detail['purchasable_id']);

                $purchase->details()->create([
                    'purchasable_type' => $modelClass,
                    'purchasable_id'   => $purchasable->id,
                    'subtotal'         => $detail['subtotal'],
                ]);
            }
        });

        return redirect()->route('purchases.index')->with('success', 'Compra creada correctamente.');
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
        $products  = Product::all(['id', 'name', 'sale_price', 'type']);
        $supplies  = Supply::all(['id', 'name', 'measure_unit']);
        return view('models.purchases.edit', compact('purchase', 'suppliers', 'products', 'supplies'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $paymentValues = implode(',', array_column(\App\Enums\PaymentStatus::cases(), 'value'));

        $validated = $request->validate([
            'supplier_id'                => 'required|exists:suppliers,id',
            'invoice_number'             => 'required|string|max:255|unique:purchases,invoice_number,' . $purchase->id,
            'date'                       => 'required|date',
            'payment_status'             => 'required|string|in:' . $paymentValues,
            'total'                      => 'required|numeric|min:0',
            'details'                    => 'required|array|min:1',
            'details.*.id'               => 'nullable|exists:purchase_details,id',
            'details.*.purchasable_type' => 'required|in:product,supply',
            'details.*.purchasable_id'   => 'required|integer',
            'details.*.subtotal'         => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            $purchase->update([
                'supplier_id'    => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'date'           => $validated['date'],
                'payment_status' => $validated['payment_status'],
                'total'          => $validated['total'],
            ]);

            $existingIds = $purchase->details()->pluck('id')->toArray();
            $updatedIds  = [];

            foreach ($validated['details'] as $detail) {
                $modelClass  = $detail['purchasable_type'] === 'product' ? Product::class : Supply::class;
                $purchasable = $modelClass::findOrFail($detail['purchasable_id']);

                $data = [
                    'purchasable_type' => $modelClass,
                    'purchasable_id'   => $purchasable->id,
                    'subtotal'         => $detail['subtotal'],
                ];

                if (isset($detail['id']) && in_array($detail['id'], $existingIds)) {
                    $purchase->details()->where('id', $detail['id'])->update($data);
                    $updatedIds[] = $detail['id'];
                } else {
                    $newDetail    = $purchase->details()->create($data);
                    $updatedIds[] = $newDetail->id;
                }
            }

            $toDelete = array_diff($existingIds, $updatedIds);
            $purchase->details()->whereIn('id', $toDelete)->delete();
        });

        return redirect()->route('purchases.index')->with('success', 'Compra actualizada correctamente.');
    }

    /**
     * EIF-170: quickStoreProduct solo guarda minimum_stock.
     * current_stock se omite intencionalmente en la creación.
     */
    public function quickStoreProduct(Request $request): JsonResponse
    {
        $rules = [
            'category_id'       => 'required|integer|exists:categories,id',
            'barcode'           => 'nullable|string',
            'name'              => 'required|string|max:255',
            'type'              => 'required|string|in:' . implode(',', array_column(\App\Enums\ProductType::cases(), 'value')),
            'has_inventory'     => 'required|boolean',
            'reference_cost'    => 'required|numeric|min:0',
            'tax_percentage'    => 'required|numeric|min:0',
            'margin_percentage' => 'required|numeric|min:0',
            'sale_price'        => 'required|numeric|min:0',
        ];

        // EIF-170: Solo se valida stock_minimo; stock_actual no se procesa en creación
        if ($request->boolean('has_inventory')) {
            $rules['stock_minimo'] = 'required|integer|min:0';
        } else {
            $rules['stock_minimo'] = 'nullable|integer|min:0';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $product = DB::transaction(function () use ($request, $validator) {
            $product = Product::create($validator->validated());

            // EIF-170: Al crear, current_stock arranca en 0 (se actualiza vía inventario)
            if ($request->boolean('has_inventory')) {
                ProductStock::create([
                    'product_id'    => $product->id,
                    'current_stock' => 0,
                    'minimum_stock' => (int) $request->input('stock_minimo', 0),
                ]);
            }

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto creado exitosamente.',
            'product' => [
                'id'         => $product->id,
                'name'       => $product->name,
                'sale_price' => $product->sale_price,
                'type'       => $product->type instanceof \App\Enums\ProductType
                    ? $product->type->value
                    : $product->type,
            ],
        ]);
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Compra eliminada correctamente.');
    }
}
