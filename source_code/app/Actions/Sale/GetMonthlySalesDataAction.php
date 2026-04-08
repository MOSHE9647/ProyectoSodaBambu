<?php

namespace App\Actions\Sale;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Sale;
use App\Enums\PaymentStatus; // <-- Asegúrate de importar tu Enum o clase
use Illuminate\Support\Facades\Log;

class GetMonthlySalesDataAction
{
    public function execute(): array
    {
        Carbon::setLocale('es');
        
        $monthStart = Carbon::now()->startOfMonth();
        $today = Carbon::now();

        // 1. Corrección: Usar la misma columna 'date' para el Raw y el Between
        // 2. Corrección: Usar copy() para no mutar la variable $today
        $sales = Sale::selectRaw('DATE(date) as date_data, SUM(total) as daily_total')
            ->whereBetween('date', [$monthStart, $today->copy()->endOfDay()])
            ->where('payment_status', PaymentStatus::PAID)
            ->groupBy('date_data')
            ->pluck('daily_total', 'date_data');

        Log::info('Monthly sales data:', ['sales' => $sales]);

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
            'monthlyTotal'       => $monthlyTotal,
            'monthlySalesLabels' => $labels,
            'monthlySalesValues' => $values,
        ];
    }
}