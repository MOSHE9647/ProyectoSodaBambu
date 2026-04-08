<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use Carbon\Carbon;

class GetDailySalesDataAction
{
    public function execute(): array
    {

        $today = Carbon::today();

        $isSqlite = \DB::connection()->getDriverName() === 'sqlite';
        $hourExpression = $isSqlite ? "strftime('%H', date)" : 'HOUR(date)';

        $sales = Sale::selectRaw("{$hourExpression} as hour, SUM(total) as output_per_hour")
            ->whereDate('date', $today)
            ->groupBy('hour')
            ->pluck('output_per_hour', 'hour');

        $dailyTotal = 0;
        $labels = [];
        $values = [];

        $openingTime = 8;  // 8 AM
        $closingTime = 22;   // 10 PM

        // Iterate over each hour of the defined schedule
        for ($i = $openingTime; $i <= $closingTime; $i++) {

            // Format the hour label for the UI (e.g., "8:00 AM", "2:00 PM")
            $formattedTime = Carbon::createFromTime($i, 0, 0)->format('g:i A');

            // Get the sale for that exact hour, if there were no sales, assign 0
            $saleForHour = $sales->get($i, 0);

            $labels[] = $formattedTime;
            $values[] = $saleForHour;
            $dailyTotal += $saleForHour;
        }

        return [
            'dailyTotal' => $dailyTotal,
            'dailySalesLabels' => $labels,
            'dailySalesValues' => $values,
        ];
    }
}
