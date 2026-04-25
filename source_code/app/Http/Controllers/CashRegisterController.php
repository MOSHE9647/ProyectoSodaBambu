<?php

namespace App\Http\Controllers;

use App\Enums\CashRegisterStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Models\CashRegister;
use App\Models\CashRegisterReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $cashRegister = CashRegister::create([
            'user_id' => auth()->id(),
            'opening_balance' => $validated['opening_balance'],
            'opened_at' => now(),
            'status' => CashRegisterStatus::OPEN,
        ]);

        // Redirect to the sales page or wherever appropriate
        return response()->json([
            'message' => 'La caja se abrió correctamente.',
            'cash_register' => $cashRegister
        ]);
    }

    public function getCloseData(CashRegister $cashRegister): JsonResponse
    {

        // Only transactions from this cash register that have an associated payment (where the method is defined)
        $transactions = $cashRegister->transactions()->with('payment')->get();

        // Group by payment method and calculate the balance (Income - Expenses)
        $methodData = $transactions->groupBy('payment.method.value')
            ->map(function ($group) {
                return $group->reduce(function ($carry, $t) {
                    return $t->type === TransactionType::INCOME
                        ? $carry + $t->amount
                        : $carry - $t->amount;
                }, 0);
            });

        // System cash includes the opening balance
        $systemCash = $cashRegister->opening_balance + $methodData->get(PaymentMethod::CASH->value, 0);

        return response()->json([
            'total_orders' => $transactions->count(),
            'system_cash' => (float) $systemCash,
            'system_card' => (float) $methodData->get(PaymentMethod::CARD->value, 0),
            'system_sinpe' => (float) $methodData->get(PaymentMethod::SINPE->value, 0),
            'system_total' => (float) ($systemCash + $methodData->get(PaymentMethod::CARD->value) + $methodData->get(PaymentMethod::SINPE->value)),
        ]);
    }

    /**
     * Processes the final cash register closure.
     */
    public function close(Request $request, CashRegister $cashRegister): JsonResponse
    {
        $validated = $request->validate([
            'physical_cash' => 'required|numeric|min:0',
            'physical_card' => 'required|numeric|min:0',
            'physical_sinpe' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            return DB::transaction(function () use ($validated, $cashRegister) {
                // Get current system data (recalculate for safety)
                $data = $this->getCloseData($cashRegister)->getData();

                // Create the main report
                $totalPhysical = $validated['physical_cash'] + $validated['physical_card'] + $validated['physical_sinpe'];
                $report = CashRegisterReport::create([
                    'cash_register_id' => $cashRegister->id,
                    'total_system_amount' => $data->system_total,
                    'total_physical_amount' => $totalPhysical,
                    'total_difference' => $totalPhysical - $data->system_total,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Create details by payment method (Cash, Card, SINPE)
                $methods = [
                    ['method' => PaymentMethod::CASH,  'sys' => $data->system_cash,  'phys' => $validated['physical_cash']],
                    ['method' => PaymentMethod::CARD,  'sys' => $data->system_card,  'phys' => $validated['physical_card']],
                    ['method' => PaymentMethod::SINPE, 'sys' => $data->system_sinpe, 'phys' => $validated['physical_sinpe']],
                ];

                foreach ($methods as $item) {
                    $report->details()->create([
                        'payment_method' => $item['method'],
                        'system_amount' => $item['sys'],
                        'physical_amount' => $item['phys'],
                        'difference' => $item['phys'] - $item['sys'],
                    ]);
                }

                // Update cash register status
                $cashRegister->update([
                    'status' => CashRegisterStatus::CLOSED,
                    'closed_at' => now(),
                    'closing_balance' => $report->total_physical_amount,
                ]);

                return response()->json(['message' => 'Caja cerrada y reporte generado con éxito.']);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al cerrar caja: '.$e->getMessage()], 500);
        }
    }
}
