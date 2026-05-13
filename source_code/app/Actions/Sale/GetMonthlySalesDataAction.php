<?php

namespace App\Actions\Sale;

use App\Models\Transaction; 
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GetMonthlySalesDataAction
{
    public function execute(): array
    {
        Carbon::setLocale('es');
        $timezone = 'America/Costa_Rica';

        $monthStartLocal = Carbon::now($timezone)->startOfMonth();
        $todayLocal = Carbon::now($timezone);

        $monthStartUtc = $monthStartLocal->copy()->timezone('UTC');
        $todayUtc = $todayLocal->copy()->endOfDay()->timezone('UTC');

        /**
         * We fetch only the incomes from the transactions table.
         * This includes: Paid sales, contract payments (installments), and manual cash register incomes.
         */
        $incomes = Transaction::incomes()
            ->whereBetween('created_at', [$monthStartUtc, $todayUtc])
            ->get(['created_at', 'amount']);

        
        $incomesByDate = $incomes->groupBy(function ($income) use ($timezone) {
            return Carbon::parse($income->created_at)->timezone($timezone)->format('Y-m-d');
        })->map(fn ($group) => $group->sum('amount'));

        $monthlyTotal = 0;
        $labels = [];
        $values = [];

        $period = CarbonPeriod::create($monthStartLocal->copy()->startOfDay(), $todayLocal->copy()->endOfDay());

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dayLabel = ucfirst($date->translatedFormat('l, j \d\e F'));

            $totalOfDay = $incomesByDate->get($dateString, 0);

            $labels[] = $dayLabel;
            $values[] = $totalOfDay;
            $monthlyTotal += $totalOfDay;
        }

        return [
            'monthlyTotal' => $monthlyTotal,
            'monthlySalesLabels' => $labels,
            'monthlySalesValues' => $values,
        ];
    }
}