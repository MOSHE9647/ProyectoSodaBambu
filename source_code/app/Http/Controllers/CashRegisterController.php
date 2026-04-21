<?php

namespace App\Http\Controllers;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Validate the request data
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
        ], [
            'opening_balance.required' => 'El monto inicial es obligatorio.',
            'opening_balance.numeric' => 'El monto inicial debe ser un número.',
            'opening_balance.min' => 'El monto inicial no puede ser negativo.',
        ]);

        CashRegister::create([
            'user_id' => auth()->id(),
            'opening_balance' => $validated['opening_balance'],
            'opened_at' => now(),
            'status' => CashRegisterStatus::OPEN,
        ]);

        // Redirect to the sales page or wherever appropriate
        return response()->json(['message' => 'La caja se abrió correctamente.']);
    }
}
