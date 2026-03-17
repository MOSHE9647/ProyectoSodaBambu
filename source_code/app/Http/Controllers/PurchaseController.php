<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Supply;
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

            // DataTable normal
            $columns = [
                'id', 'invoice_number', 'supplier_id', 'date', 'total', 'payment_status'
            ];

            $query = Purchase::with('supplier:id,name');

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
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

            if ($request->has('order')) {
                $orderColumn = $columns[$request->order[0]['column']];
                $orderDir    = $request->order[0]['dir'];
                $query->orderBy($orderColumn, $orderDir);
            }

            $recordsTotal    = Purchase::count();
            $recordsFiltered = $query->count();

            $purchases = $query->skip($request->start)
                               ->take($request->length)
                               ->get();

            return response()->json([
                'draw'            => $request->draw,
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
        $products  = Product::all(['id', 'name', 'sale_price']);
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
            'details'                    => 'required|array|min:1',
            'details.*.purchasable_type' => 'required|in:product,supply',
            'details.*.purchasable_id'   => 'required|integer',
            'details.*.quantity'         => 'required|integer|min:1',
            'details.*.unit_price'       => 'required|numeric|min:0',
            'details.*.expiration_date'  => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated) {
            $total = collect($validated['details'])->sum(
                fn($d) => $d['quantity'] * $d['unit_price']
            );

            $purchase = Purchase::create([
                'supplier_id'    => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'date'           => $validated['date'],
                'payment_status' => $validated['payment_status'],
                'total'          => $total,
            ]);

            foreach ($validated['details'] as $detail) {
                $modelClass  = $detail['purchasable_type'] === 'product' ? Product::class : Supply::class;
                $purchasable = $modelClass::findOrFail($detail['purchasable_id']);

                $purchase->details()->create([
                    'purchasable_type' => $modelClass,
                    'purchasable_id'   => $purchasable->id,
                    'quantity'         => $detail['quantity'],
                    'unit_price'       => $detail['unit_price'],
                    'subtotal'         => $detail['quantity'] * $detail['unit_price'],
                    'expiration_date'  => $detail['expiration_date'] ?? null,
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
        $products  = Product::all(['id', 'name', 'sale_price']);
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
            'details'                    => 'required|array|min:1',
            'details.*.id'               => 'nullable|exists:purchase_details,id',
            'details.*.purchasable_type' => 'required|in:product,supply',
            'details.*.purchasable_id'   => 'required|integer',
            'details.*.quantity'         => 'required|integer|min:1',
            'details.*.unit_price'       => 'required|numeric|min:0',
            'details.*.expiration_date'  => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated, $purchase) {
            $total = collect($validated['details'])->sum(
                fn($d) => $d['quantity'] * $d['unit_price']
            );

            $purchase->update([
                'supplier_id'    => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'date'           => $validated['date'],
                'payment_status' => $validated['payment_status'],
                'total'          => $total,
            ]);

            $existingIds = $purchase->details()->pluck('id')->toArray();
            $updatedIds  = [];

            foreach ($validated['details'] as $detail) {
                $modelClass  = $detail['purchasable_type'] === 'product' ? Product::class : Supply::class;
                $purchasable = $modelClass::findOrFail($detail['purchasable_id']);

                $data = [
                    'purchasable_type' => $modelClass,
                    'purchasable_id'   => $purchasable->id,
                    'quantity'         => $detail['quantity'],
                    'unit_price'       => $detail['unit_price'],
                    'subtotal'         => $detail['quantity'] * $detail['unit_price'],
                    'expiration_date'  => $detail['expiration_date'] ?? null,
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

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();
        return redirect()->route('purchases.index')->with('success', 'Compra eliminada correctamente.');
    }
}