<?php

namespace App\Actions\Sale;

use App\Enums\PaymentStatus;
use App\Models\Sale;
use Carbon\Carbon;

class CalculateDailySalesTrendAction
{
    /**
     * Calculates the total sales for today and compares it with yesterday's sales
     * up to the current time to determine the sales trend.
     *
     * This method retrieves today's complete daily sales total and compares it against
     * yesterday's sales up to the current hour to calculate a percentage change trend.
     * The trend direction is determined as 'up', 'down', or 'neutral' based on whether
     * sales increased, decreased, or remained the same.
     *
     * Edge cases:
     * - If yesterday had no sales (0), the trend is set to 100% if today has sales, or 0% if not
     * - If today has no sales and yesterday had none either, the trend is 0%
     *
     * @return array{todaySalesTotal: float, salesTrendText: string, trendDirection: string}
     */
    public function execute(): array
    {
        // 1. Get today's total (full day)
        $todayTotal = $this->getSalesTotal(Carbon::today());

        // 2. Get yesterday's total up to the current time (for trend comparison)
        $yesterdayUntilNow = $this->getSalesTotal(Carbon::yesterday(), true);

        // 3. Calculate trend
        if ($yesterdayUntilNow > 0) {
            $percentage = (($todayTotal - $yesterdayUntilNow) / $yesterdayUntilNow) * 100;
        } else {
            $percentage = $todayTotal > 0 ? 100 : 0;
        }

        $trendDirection = $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'neutral');
        $trendSign = $percentage > 0 ? '+' : '';
        $salesTrendText = $trendSign.round($percentage).'%';

        return [
            'todaySalesTotal' => $todayTotal,
            'salesTrendText' => $salesTrendText,
            'trendDirection' => $trendDirection,
        ];
    }

    /**
     * Get the total sales amount for a given date.
     *
     * @param  Carbon  $date  The date to calculate sales total for
     * @param  bool  $untilCurrentTime  If true, only include sales up to the current time of day. Defaults to false.
     * @return float The sum of all sales totals for the specified date and criteria
     */
    private function getSalesTotal(Carbon $date, bool $untilCurrentTime = false): float
    {
        $query = Sale::whereDate('date', $date)
            ->where('payment_status', PaymentStatus::PAID);

        if ($untilCurrentTime) {
            $query->whereTime('date', '<=', Carbon::now()->toTimeString());
        }

        return (float) $query->sum('total');
    }
}
