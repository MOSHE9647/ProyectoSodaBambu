<?php

namespace App\Actions\Sale;

use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Sale;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GetSalesReportDataAction
{
    /**
     * Build the sales report data using the requested filters.
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
            ->with('user')
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
            ->get(['id', 'user_id', 'invoice_number', 'payment_status', 'date', 'total']);

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

        $topProducts = $this->buildTopProducts(
            $startLocal,
            $endLocal,
            $paymentStatus,
            $activeProductType,
            $activeCategoryId
        );

        [$previousStartLocal, $previousEndLocal, $previousPeriodLabel] = $this->resolvePreviousPeriod($startLocal, $endLocal);
        $previousTopProducts = $this->buildTopProducts(
            $previousStartLocal,
            $previousEndLocal,
            $paymentStatus,
            $activeProductType,
            $activeCategoryId
        );

        $totalSoldUnits = (int) collect($topProducts)->sum('sold_quantity');
        $productsInRanking = count($topProducts);
        $averageUnitsPerDay = $daysInPeriod > 0 ? $totalSoldUnits / $daysInPeriod : 0;
        $previousDaysInPeriod = max($previousStartLocal->copy()->startOfDay()->diffInDays($previousEndLocal->copy()->startOfDay()) + 1, 1);
        $previousTotalSoldUnits = (int) collect($previousTopProducts)->sum('sold_quantity');
        $previousAverageUnitsPerDay = $previousDaysInPeriod > 0 ? $previousTotalSoldUnits / $previousDaysInPeriod : 0;

        if ($previousAverageUnitsPerDay > 0) {
            $averageUnitsVariationPercent = (($averageUnitsPerDay - $previousAverageUnitsPerDay) / $previousAverageUnitsPerDay) * 100;
        } elseif ($averageUnitsPerDay > 0) {
            $averageUnitsVariationPercent = 100;
        } else {
            $averageUnitsVariationPercent = 0;
        }

        $averageUnitsTrendDirection = $averageUnitsVariationPercent >= 0 ? 'up' : 'down';

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
            'totalSoldUnits' => $totalSoldUnits,
            'productsInRanking' => $productsInRanking,
            'averageUnitsPerDay' => $averageUnitsPerDay,
            'averageUnitsVariationPercent' => round(abs($averageUnitsVariationPercent), 1),
            'averageUnitsTrendDirection' => $averageUnitsTrendDirection,
            'previousPeriodLabel' => $previousPeriodLabel,
            'dailyReports' => $dailyReports,
            'categories' => $categories,
            'topProducts' => $topProducts,
            'sales' => $sales,
        ];
    }

    /**
     * Build top-selling products with quantity, income, and share percentage.
     */
    private function buildTopProducts(
        Carbon $startLocal,
        Carbon $endLocal,
        string $paymentStatus,
        string $activeProductType,
        ?int $activeCategoryId,
    ): array {
        if (! Schema::hasTable('sale_details')) {
            return [];
        }

        $requiredColumns = [
            'sale_id',
            'product_id',
            'quantity',
            'unit_price',
            'subtotal',
            'deleted_at',
        ];

        if (! Schema::hasColumns('sale_details', $requiredColumns)) {
            return [];
        }

        $query = DB::table('sale_details as sd')
            ->join('sales as s', 's.id', '=', 'sd.sale_id')
            ->join('products as p', 'p.id', '=', 'sd.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->whereNull('sd.deleted_at')
            ->whereNull('s.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereBetween('s.date', [
                $startLocal->copy()->timezone('UTC'),
                $endLocal->copy()->timezone('UTC'),
            ]);

        $paymentStatusValues = array_map(
            fn (PaymentStatus $status) => $status->value,
            PaymentStatus::cases()
        );

        if (in_array($paymentStatus, $paymentStatusValues, true)) {
            $query->where('s.payment_status', $paymentStatus);
        }

        if ($activeCategoryId) {
            $query->where('p.category_id', $activeCategoryId);
        }

        if ($activeProductType === 'merchandise') {
            $query->where('p.type', ProductType::MERCHANDISE->value);
        }

        if ($activeProductType === 'dishes') {
            $query->whereIn('p.type', [
                ProductType::DISH->value,
                ProductType::DRINK->value,
                ProductType::PACKAGED->value,
            ]);
        }

        $products = $query
            ->groupBy('p.id', 'p.name', 'p.type', 'c.name')
            ->selectRaw('p.id as product_id, p.name as product_name, p.type as product_type, c.name as category_name, SUM(sd.quantity) as sold_quantity, SUM(sd.subtotal) as income')
            ->orderByDesc('sold_quantity')
            ->orderByDesc('income')
            ->limit(30)
            ->get();

        if ($products->isEmpty()) {
            return [];
        }

        $totalSoldQuantity = max((int) $products->sum('sold_quantity'), 1);

        return $products
            ->map(function (object $product) use ($totalSoldQuantity): array {
                $quantity = (int) $product->sold_quantity;
                $income = (float) $product->income;
                $productType = ProductType::tryFrom((string) $product->product_type);

                return [
                    'product_name' => (string) $product->product_name,
                    'category_name' => (string) ($product->category_name ?? 'Sin categoría'),
                    'product_type_label' => $productType?->label() ?? (string) $product->product_type,
                    'sold_quantity' => $quantity,
                    'income' => round($income, 2),
                    'total_percent' => round(($quantity / $totalSoldQuantity) * 100, 1),
                ];
            })
            ->values()
            ->all();
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
     * Resolve previous period range using the same amount of days as current period.
     */
    private function resolvePreviousPeriod(Carbon $startLocal, Carbon $endLocal): array
    {
        $totalDays = max($startLocal->copy()->startOfDay()->diffInDays($endLocal->copy()->startOfDay()) + 1, 1);

        $previousEndLocal = $startLocal->copy()->subDay()->endOfDay();
        $previousStartLocal = $previousEndLocal->copy()->startOfDay()->subDays($totalDays - 1);
        $previousPeriodLabel = $previousStartLocal->translatedFormat('d/m/Y').' - '.$previousEndLocal->translatedFormat('d/m/Y');

        return [$previousStartLocal, $previousEndLocal, $previousPeriodLabel];
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
