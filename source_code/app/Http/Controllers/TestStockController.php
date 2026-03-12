<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductStock;

class TestStockController extends Controller
{
    public function triggerLowStock(ProductStock $stock)
    {
        // 1. Aseguramos que el stock actual sea mayor al mínimo para que el cambio sea evidente
        // Esto disparará el evento 'updated' en el ProductStockObserver
        // $stock->current_stock = $stock->minimum_stock - 1;
        // $stock->save();
        $stock->update([
            'current_stock' => $stock->minimum_stock - 1
        ]);

        // 3. Redireccionamos a cualquier vista que use app.blade.php
        // El 'session()->flash('warning', ...)' del observer se mostrará aquí
        return redirect()->back()->with('success', '¡Simulación completada! Revisa si apareció el Toast de advertencia.');
    }
}
