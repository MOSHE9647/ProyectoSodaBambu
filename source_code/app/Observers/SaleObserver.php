<?php

namespace App\Observers;

use App\Actions\Sale\CalculateDailySalesTrendAction;
use App\Actions\Sale\GetDailySalesDataAction;
use App\Actions\Sale\GetMonthlySalesDataAction;
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
        $sale->details->each(fn ($detail) => $detail->delete());

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
        $sale->details()->withTrashed()->each(fn ($detail) => $detail->restore());
        $sale->payments()->withTrashed()->each(fn ($payment) => $payment->restore());

        $this->refreshSalesCache();
    }

    /**
     * Helper to refresh sales-related cache entries after changes to sales data.
     */
    private function refreshSalesCache(): void
    {
        // Force cache refresh by forgetting the previous key before executing the remember
        Cache::forget('today_sales_stats');
        Cache::forget('monthly_sales_stats');
        Cache::forget('daily_sales_stats');

        // Get the latest sales stats and cache them for 10 minutes
        Cache::remember('today_sales_stats', now()->addMinutes(10), function () {
            return $this->calculateDailySalesTrendAction->execute();
        });

        Cache::remember('monthly_sales_stats', now()->addMinutes(10), function () {
            return app(GetMonthlySalesDataAction::class)->execute();
        });

        Cache::remember('daily_sales_stats', now()->addMinutes(10), function () {
            return app(GetDailySalesDataAction::class)->execute();
        });
    }
}
