<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductStock;

class TestStockController extends Controller
{
    public function triggerLowStock(ProductStock $stock)
    {
        // 1. We ensure the current stock is greater than the minimum so the change is evident
        // This will trigger the 'updated' event in the ProductStockObserver
        $stock->update([
            'current_stock' => $stock->minimum_stock - 1
        ]);

        // 3. Redirect to any view that uses app.blade.php
        // The 'session()->put('warning', ...)' from the observer will be displayed here
        return redirect()->back()->with('success', '¡Simulación completada! Revisa si apareció el Toast de advertencia.');
    }
}
