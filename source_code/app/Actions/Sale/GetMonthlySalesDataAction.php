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
        $timezone = 'America/Costa_Rica';

        $monthStart = Carbon::now()->startOfMonth();
        $today = Carbon::now();

        $sales = Sale::whereBetween('date', [$monthStart, $today])
            ->where('payment_status', PaymentStatus::PAID)
            ->get(['date', 'total']);

        $salesByDate = $sales->groupBy(function ($sale) use ($timezone) {
            return Carbon::parse($sale->date)->timezone($timezone)->format('Y-m-d');
        })->map(function ($group) {
            return $group->sum('total');
        });

        $monthlyTotal = 0;
        $labels = [];
        $values = [];

        $period = CarbonPeriod::create($monthStart, $today);

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dayLabel = ucfirst($date->translatedFormat('l, j \d\e F'));

            $todaySale = $salesByDate->get($dateString, 0);

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
