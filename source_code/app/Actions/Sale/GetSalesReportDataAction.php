<?php

namespace App\Actions\Sale;

use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Sale;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;

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
        $sort = $this->resolveSortColumn($filters['sort'] ?? 'date');
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $activeProductType = $this->resolveProductTypeScope((string) ($filters['product_type'] ?? 'all'));
        $activeCategoryId = $this->resolveCategoryId($filters['category_id'] ?? null);

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
                'date_raw' => $date->format('Y-m-d'),
                'date' => $date->format('d/m/Y'),
                'orders' => $orders,
                'income' => $income,
                'avg_ticket' => $orders > 0 ? $income / $orders : 0,
            ];
        }

        $dailyReports = collect($dailyReports)
            ->sortBy(function (array $report) use ($sort) {
                return match ($sort) {
                    'orders' => $report['orders'],
                    'income' => $report['income'],
                    'avg_ticket' => $report['avg_ticket'],
                    default => $report['date_raw'],
                };
            }, SORT_REGULAR, $direction === 'desc')
            ->values()
            ->map(fn (array $report) => Arr::except($report, ['date_raw']))
            ->all();

        $totalIncome = (float) $sales->sum('total');
        $totalOrders = $sales->count();
        $daysInPeriod = max($startLocal->copy()->startOfDay()->diffInDays($endLocal->copy()->startOfDay()) + 1, 1);
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $activeCategoryName = $activeCategoryId
            ? $categories->firstWhere('id', $activeCategoryId)?->name
            : null;

        $topProducts = collect();

        $totalSoldQuantity = max((int) $topProducts->sum('sold_quantity'), 1);
        $topProducts = $topProducts->map(function (array $product) use ($totalSoldQuantity) {
            $percentage = ((int) $product['sold_quantity'] / $totalSoldQuantity) * 100;

            return array_merge($product, [
                'total_percent' => round($percentage, 1),
            ]);
        })->all();

        return [
            'activePeriod' => $filters['period'] ?? 'month',
            'activePaymentStatus' => $paymentStatus,
            'activeProductType' => $activeProductType,
            'activeProductTypeLabel' => $this->getProductTypeScopeLabel($activeProductType),
            'activeCategoryId' => $activeCategoryId,
            'activeCategoryName' => $activeCategoryName,
            'periodLabel' => $periodLabel,
            'sort' => $sort,
            'direction' => $direction,
            'totalIncome' => $totalIncome,
            'totalOrders' => $totalOrders,
            'dailyAverage' => $daysInPeriod > 0 ? $totalIncome / $daysInPeriod : 0,
            'dailyReports' => $dailyReports,
            'categories' => $categories,
            'topProducts' => $topProducts,
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

    /**
     * Resolve the sortable column used by the report table.
     */
    private function resolveSortColumn(string $sort): string
    {
        return in_array($sort, ['date', 'orders', 'income', 'avg_ticket'], true) ? $sort : 'date';
    }

    /**
     * Resolve the selectable product type scope for reports.
     */
    private function resolveProductTypeScope(string $scope): string
    {
        return in_array($scope, ['all', 'merchandise', 'dishes'], true) ? $scope : 'all';
    }

    /**
     * Resolve category id from filters.
     */
    private function resolveCategoryId(mixed $categoryId): ?int
    {
        if ($categoryId === null || $categoryId === '') {
            return null;
        }

        return (int) $categoryId;
    }

    /**
     * Human label for the selected product type scope.
     */
    private function getProductTypeScopeLabel(string $scope): string
    {
        return match ($scope) {
            'merchandise' => 'Mercancía',
            'dishes' => 'Platillos',
            default => 'Todos',
        };
    }
}
