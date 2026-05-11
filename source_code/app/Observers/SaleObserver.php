<?php

namespace App\Observers;

use App\Actions\Sale\CalculateDailySalesTrendAction;
use App\Models\Sale;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Cache;

class SaleObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        protected CalculateDailySalesTrendAction $calculateDailySalesTrendAction
    ) {}

    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        // Don't need to process payment here since UpsertSaleAction already handles it.
        $this->refreshSalesCache();
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        // If the total or status has changed, refresh the cache.
        // Synchronization of payment details is now handled in the UpsertSaleAction.
        $this->refreshSalesCache();
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        $sale->saleDetails->each(fn ($detail) => $detail->delete());

        $sale->payments->each(function ($payment) {
            $payment->transaction?->delete();
            $payment->delete();
        });

        $this->refreshSalesCache();

    }

    /**
     * Handle the Sale "restored" event.
     */
    public function restored(Sale $sale): void
    {
        $sale->saleDetails()->withTrashed()->each(fn ($detail) => $detail->restore());
        $sale->payments()->withTrashed()->each(fn ($payment) => $payment->restore());

        $this->refreshSalesCache();
    }

    /**
     * Helper to refresh sales-related cache entries after changes to sales data.
     */
    private function refreshSalesCache(): void
    {
        // Recompute and store the today's sales stats so tests and dashboard remain consistent
        try {
            $todayStats = $this->calculateDailySalesTrendAction->execute();
            Cache::put('today_sales_stats', $todayStats, now()->addMinutes(10));
        } catch (\Throwable $e) {
            // If the calculation fails for any reason, fall back to forgetting the key
            Cache::forget('today_sales_stats');
        }
        Cache::forget('monthly_sales_stats');
        Cache::forget('daily_sales_stats');
        Cache::forget('top_selling_products');
    }
}
