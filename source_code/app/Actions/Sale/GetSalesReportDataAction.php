<?php

namespace App\Actions\Sale;

use App\Enums\PaymentStatus;
use App\Models\Sale;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GetSalesReportDataAction
{
    /**
     * Build the sales report data using the requested filters.
     *
     * The current schema only stores the sale header, so the report is filtered
     * by date range and payment status. Product-type and category filters will
     * require a sale details relation in a future iteration.
     */
    public function execute(array $filters = []): array
    {
        $timezone = 'America/Costa_Rica';
        [$startLocal, $endLocal, $periodLabel] = $this->resolvePeriod($filters, $timezone);

        $paymentStatus = $filters['payment_status'] ?? PaymentStatus::PAID->value;

        $query = Sale::query()
            ->with('employee.user')
            ->whereBetween('date', [
                $startLocal->copy()->timezone('UTC'),
                $endLocal->copy()->timezone('UTC'),
            ]);

        $paymentStatusValues = array_map(
            fn (PaymentStatus $status) => $status->value,
            PaymentStatus::cases()
        );

        if (in_array($paymentStatus, $paymentStatusValues, true)) {
            $query->where('payment_status', $paymentStatus);
        }

        $sales = $query
            ->orderBy('date', 'desc')
            ->get(['id', 'employee_id', 'invoice_number', 'payment_status', 'date', 'total']);

        $salesByDate = $sales->groupBy(function (Sale $sale) use ($timezone) {
            return Carbon::parse($sale->date)->timezone($timezone)->format('Y-m-d');
        });

        $dailyReports = [];
        $period = CarbonPeriod::create(
            $startLocal->copy()->startOfDay(),
            '1 day',
            $endLocal->copy()->startOfDay(),
        );

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            $salesForDay = $salesByDate->get($dateKey, collect());
            $income = (float) $salesForDay->sum('total');
            $orders = $salesForDay->count();

            $dailyReports[] = [
                'date' => $date->format('d/m/Y'),
                'orders' => $orders,
                'income' => $income,
                'avg_ticket' => $orders > 0 ? $income / $orders : 0,
            ];
        }

        $totalIncome = (float) $sales->sum('total');
        $totalOrders = $sales->count();
        $daysInPeriod = max($startLocal->copy()->startOfDay()->diffInDays($endLocal->copy()->startOfDay()) + 1, 1);

        return [
            'activePeriod' => $filters['period'] ?? 'month',
            'activePaymentStatus' => $paymentStatus,
            'periodLabel' => $periodLabel,
            'totalIncome' => $totalIncome,
            'totalOrders' => $totalOrders,
            'dailyAverage' => $daysInPeriod > 0 ? $totalIncome / $daysInPeriod : 0,
            'dailyReports' => $dailyReports,
            'sales' => $sales,
        ];
    }

    /**
     * Resolve the period filter into a concrete date range.
     */
    private function resolvePeriod(array $filters, string $timezone): array
    {
        $period = $filters['period'] ?? 'month';
        $now = Carbon::now($timezone);

        return match ($period) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->translatedFormat('d/m/Y'),
            ],
            'week' => [
                $now->copy()->startOfWeek()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->copy()->startOfWeek()->translatedFormat('d/m/Y').' - '.$now->translatedFormat('d/m/Y'),
            ],
            'custom' => $this->resolveCustomPeriod($filters, $timezone, $now),
            default => [
                $now->copy()->startOfMonth()->startOfDay(),
                $now->copy()->endOfDay(),
                $now->copy()->startOfMonth()->translatedFormat('d/m/Y').' - '.$now->translatedFormat('d/m/Y'),
            ],
        };
    }

    /**
     * Resolve a custom period using the submitted start and end dates.
     */
    private function resolveCustomPeriod(array $filters, string $timezone, Carbon $fallback): array
    {
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if (! $startDate || ! $endDate) {
            return [
                $fallback->copy()->startOfMonth()->startOfDay(),
                $fallback->copy()->endOfDay(),
                $fallback->copy()->startOfMonth()->translatedFormat('d/m/Y').' - '.$fallback->translatedFormat('d/m/Y'),
            ];
        }

        $startLocal = Carbon::parse($startDate, $timezone)->startOfDay();
        $endLocal = Carbon::parse($endDate, $timezone)->endOfDay();

        if ($endLocal->lessThan($startLocal)) {
            [$startLocal, $endLocal] = [$endLocal, $startLocal];
        }

        return [
            $startLocal,
            $endLocal,
            $startLocal->translatedFormat('d/m/Y').' - '.$endLocal->translatedFormat('d/m/Y'),
        ];
    }
}
