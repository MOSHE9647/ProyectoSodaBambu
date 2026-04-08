<?php

namespace App\Actions\Sale;

use App\Enums\PaymentStatus;
use App\Models\Sale;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GetMonthlySalesDataAction
{
    public function execute(): array
    {
        Carbon::setLocale('es');

        $monthStart = Carbon::now()->startOfMonth();
        $today = Carbon::now();

        $sales = Sale::selectRaw('DATE(date) as date_data, SUM(total) as daily_total')
            ->whereBetween('date', [$monthStart, $today->copy()->endOfDay()])
            ->where('payment_status', PaymentStatus::PAID)
            ->groupBy('date_data')
            ->pluck('daily_total', 'date_data');

        $monthlyTotal = 0;
        $labels = [];
        $values = [];

        $period = CarbonPeriod::create($monthStart, $today);

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dayLabel = ucfirst($date->translatedFormat('l, j \d\e F'));

            $todaySale = $sales->get($dateString, 0);

            $labels[] = $dayLabel;
            $values[] = $todaySale;
            $monthlyTotal += $todaySale;
        }

        return [
            'monthlyTotal' => $monthlyTotal,
            'monthlySalesLabels' => $labels,
            'monthlySalesValues' => $values,
        ];
    }
}
