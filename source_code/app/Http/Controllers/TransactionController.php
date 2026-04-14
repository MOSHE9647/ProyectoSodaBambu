<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    /**
     * Lista los movimientos financieros.
     */
    public function index(): JsonResponse
    {
        // Aplicamos Eager Loading para evitar problemas de N+1
        $transactions = Transaction::with(['payment.origin'])
            ->latest()
            ->paginate(15);

        return response()->json($transactions);
    }
}
