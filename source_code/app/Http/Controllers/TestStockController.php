<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;

class TestStockController extends Controller
{
    public function triggerLowStock($id)
    {
        $stock = ProductStock::findOrFail($id);
        // 1. We ensure the current stock is greater than the minimum so the change is evident
        // This will trigger the 'updated' event in the ProductStockObserver
        $stock->update([
            'current_stock' => $stock->minimum_stock - 1,
        ]);

        return redirect()->back();
    }
}
