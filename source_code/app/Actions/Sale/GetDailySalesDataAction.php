<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use Carbon\Carbon;

class GetDailySalesDataAction
{
    public function execute(): array
    {
        // Define timezone for Costa Rica (UTC-6)
        $timezone = 'America/Costa_Rica';

        // Get the start and end of the current day in UTC-6 timezone
        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();

        // Get all sales for the current day
        $sales = Sale::whereBetween('date', [$startOfDay, $endOfDay])
            ->get(['date', 'total']);

        // Group sales by hour and sum totals
        $salesByHour = $sales->groupBy(function ($sale) use ($timezone) {
            // Converts the sale date to the specified timezone and formats it to get the hour (0-23)
            return Carbon::parse($sale->date)->timezone($timezone)->format('G');
        })->map(function ($group) {
            // Sum the total for each hour group
            return $group->sum('total');
        });

        $dailyTotal = 0;
        $labels = [];
        $values = [];

        $openingTime = 7;  // 7 AM
        $closingTime = 22; // 10 PM

        // Iterate through each hour of the day from opening to closing time
        for ($i = $openingTime; $i <= $closingTime; $i++) {
            $formattedTime = Carbon::createFromTime($i, 0, 0, $timezone)->format('g:i A');

            // Get the total sales for the current hour, defaulting to 0 if there are no sales
            $saleForHour = $salesByHour->get((string) $i, 0);

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
