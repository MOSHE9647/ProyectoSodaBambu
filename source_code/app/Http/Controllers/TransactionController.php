<?php

namespace App\Http\Controllers;

use App\Actions\Finance\GetFinanceSummaryAction;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    /**
     * Lista los movimientos financieros.
     */
    public function index(GetFinanceSummaryAction $getSummary): JsonResponse
    {
        $transactions = Transaction::with(['payment.origin'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'summary' => $getSummary->execute(),
            'transactions' => $transactions,
        ]);
    }
}
