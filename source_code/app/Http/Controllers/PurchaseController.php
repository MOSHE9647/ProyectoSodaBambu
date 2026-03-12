<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;   // si existe
use App\Models\Supply;    // si existe
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $columns = [
                'id', 'invoice_number', 'supplier_id', 'date', 'total', 'payment_status'
            ];

            $query = Purchase::with('supplier:id,name');

            // Filtro global de búsqueda
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%$search%")
                      ->orWhere('date', 'like', "%$search%")
                      ->orWhere('total', 'like', "%$search%")
                      ->orWhere('payment_status', 'like', "%$search%")
                      ->orWhereHas('supplier', function($sq) use ($search) {
                          $sq->where('name', 'like', "%$search%");
                      });
                });
            }

            // Ordenamiento
            if ($request->has('order')) {
                $orderColumn = $columns[$request->order[0]['column']];
                $orderDir = $request->order[0]['dir'];
                $query->orderBy($orderColumn, $orderDir);
            }

            $recordsTotal = Purchase::count();
            $recordsFiltered = $query->count();

            $purchases = $query->skip($request->start)
                               ->take($request->length)
                               ->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $purchases->map(function ($purchase) {
                    return [
                        'id' => $purchase->id,
                        'invoice_number' => $purchase->invoice_number,
                        'supplier' => [
                            'name' => $purchase->supplier->name
                        ],
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
        $products = Product::all(['id', 'name', 'sale_price']);
        $supplies = Supply::all(['id', 'name', 'measure_unit']);
        return view('models.purchases.create', compact('suppliers', 'products', 'supplies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:255|unique:purchases,invoice_number',
            'date' => 'required|date',
            //'payment_status' => 'required|in:' . implode(',', \App\Enums\PaymentStatus::values()),
            'details' => 'required|array|min:1',
            'details.*.purchasable_type' => 'required|in:product,supply', // o el modelo completo
            'details.*.purchasable_id' => 'required|integer',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
            'details.*.expiration_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $total = collect($request->details)->sum(function ($detail) {
                return $detail['quantity'] * $detail['unit_price'];
            });

            $purchase = Purchase::create([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'date' => $validated['date'],
                'payment_status' => $validated['payment_status'],
                'total' => $total,
            ]);

            foreach ($request->details as $detail) {
                // Determinar la clase del modelo según el tipo
                $modelClass = $detail['purchasable_type'] === 'product' ? Product::class : Supply::class;
                $purchasable = $modelClass::findOrFail($detail['purchasable_id']);

                $purchase->details()->create([
                    'purchasable_type' => $modelClass,
                    'purchasable_id' => $purchasable->id,
                    'quantity' => $detail['quantity'],
                    'unit_price' => $detail['unit_price'],
                    'subtotal' => $detail['quantity'] * $detail['unit_price'],
                    'expiration_date' => $detail['expiration_date'] ?? null,
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
        $products = Product::all(['id', 'name', 'sale_price']);
        $supplies = Supply::all(['id', 'name', 'measure_unit']);
        return view('models.purchases.edit', compact('purchase', 'suppliers', 'products', 'supplies'));
    }

    public function update(Request $request, Purchase $purchase)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:255|unique:purchases,invoice_number,' . $purchase->id,
            'date' => 'required|date',
           // 'payment_status' => 'required|in:' . implode(',', \App\Enums\PaymentStatus::values()),
            'details' => 'required|array|min:1',
            'details.*.id' => 'nullable|exists:purchase_details,id',
            'details.*.purchasable_type' => 'required|in:product,supply',
            'details.*.purchasable_id' => 'required|integer',
            'details.*.quantity' => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
            'details.*.expiration_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated, $request, $purchase) {
            $total = collect($request->details)->sum(function ($detail) {
                return $detail['quantity'] * $detail['unit_price'];
            });

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'date' => $validated['date'],
                'payment_status' => $validated['payment_status'],
                'total' => $total,
            ]);

            // Sincronizar detalles: obtener IDs existentes
            $existingIds = $purchase->details()->pluck('id')->toArray();
            $updatedIds = [];

            foreach ($request->details as $detail) {
                $modelClass = $detail['purchasable_type'] === 'product' ? Product::class : Supply::class;
                $purchasable = $modelClass::findOrFail($detail['purchasable_id']);

                $data = [
                    'purchasable_type' => $modelClass,
                    'purchasable_id' => $purchasable->id,
                    'quantity' => $detail['quantity'],
                    'unit_price' => $detail['unit_price'],
                    'subtotal' => $detail['quantity'] * $detail['unit_price'],
                    'expiration_date' => $detail['expiration_date'] ?? null,
                ];

                if (isset($detail['id']) && in_array($detail['id'], $existingIds)) {
                    // Actualizar existente
                    $purchase->details()->where('id', $detail['id'])->update($data);
                    $updatedIds[] = $detail['id'];
                } else {
                    // Crear nuevo
                    $newDetail = $purchase->details()->create($data);
                    $updatedIds[] = $newDetail->id;
                }
            }

            // Eliminar los que no llegaron
            $toDelete = array_diff($existingIds, $updatedIds);
            $purchase->details()->whereIn('id', $toDelete)->delete();
        });

        return redirect()->route('purchases.index')->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete(); // soft delete
        return redirect()->route('purchases.index')->with('success', 'Compra eliminada correctamente.');
    }
}