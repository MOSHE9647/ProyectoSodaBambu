<?php

namespace App\Actions\Sale;

use App\Models\Transaction;
use Carbon\Carbon;

class GetDailySalesDataAction
{
    public function execute(): array
    {
        // Define timezone for Costa Rica (UTC-6)
        $timezone = 'America/Costa_Rica';

        // Get the start and end of the current day in UTC-6 timezone and convert to UTC for DB
        $startOfDay = Carbon::now($timezone)->startOfDay()->timezone('UTC');
        $endOfDay = Carbon::now($timezone)->endOfDay()->timezone('UTC');

        /**
         * We retrieve today's incomes from the transactions table.
         * We use the incomes() scope defined in the Transaction model.
         */
        $incomes = Transaction::incomes()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get(['created_at', 'amount']);

        // Group incomes by hour (0-23)
        $incomesByHour = $incomes->groupBy(function ($income) use ($timezone) {
            return Carbon::parse($income->created_at)->timezone($timezone)->format('G');
        })->map(fn ($group) => $group->sum('amount'));

        $dailyTotal = 0;
        $labels = [];
        $values = [];

        $openingTime = 7;  // 7 AM or the time when the business opens
        $now = Carbon::now($timezone)->hour;

        // Iterate from opening time to current hour
        for ($i = $openingTime; $i <= $now; $i++) {
            $formattedTime = Carbon::createFromTime($i, 0, 0, $timezone)->format('g:i A');

            // Get the total income for the current hour
            $incomeForHour = $incomesByHour->get((string) $i, 0);

            $labels[] = $formattedTime;
            $values[] = $incomeForHour;
            $dailyTotal += $incomeForHour;
        }

        return [
            'dailyTotal' => $dailyTotal,
            'dailySalesLabels' => $labels,
            'dailySalesValues' => $values,
        ];
    }
}
