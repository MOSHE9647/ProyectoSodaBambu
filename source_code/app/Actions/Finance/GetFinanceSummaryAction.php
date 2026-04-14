<?php

namespace App\Actions\Finance;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class GetFinanceSummaryAction
{
    /**
    * Retrieves a financial summary with totals of income, expenses, and balance.
    *
    * @param string|null $startDate Start date for filtering (optional)
    * @param string|null $endDate End date for filtering (optional)
    * @return array Financial summary with total_income, total_expense, and balance
     */
    public function execute(?string $startDate = null, ?string $endDate = null): array
    {
        $query = Transaction::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $totals = $query->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                // Forzamos el uso del valor del Enum (ej: 'income') como clave del array
                // Force using the Enum value (e.g: 'income') as the array key
                return [$item->type->value => (float) $item->total];
            });

        $income = $totals[TransactionType::INCOME->value] ?? 0.0;
        $expense = $totals[TransactionType::EXPENSE->value] ?? 0.0;

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'balance' => $income - $expense,
        ];
    }
}