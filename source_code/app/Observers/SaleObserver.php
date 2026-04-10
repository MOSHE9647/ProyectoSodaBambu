<?php

namespace App\Observers;

use App\Actions\Sale\CalculateDailySalesTrendAction;
use App\Actions\Sale\GetDailySalesDataAction;
use App\Actions\Sale\GetMonthlySalesDataAction;
use App\Enums\PaymentStatus;
use App\Models\Sale;
use Illuminate\Support\Facades\Cache;

class SaleObserver
{
    public function __construct(protected CalculateDailySalesTrendAction $calculateDailySalesTrendAction) {}

    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        if ($sale->payment_status === PaymentStatus::PAID) {
            $this->refreshSalesCache();
        }
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        // Checks if the sale has changed its payment status to PAID or if it was already paid
        // and related values were modified. In either case, updates the sales cache to reflect
        // the most recent changes.
        $becamePaid = $sale->wasChanged('payment_status') && $sale->payment_status === PaymentStatus::PAID;
        $isStillPaidAndValuesChanged = $sale->payment_status === PaymentStatus::PAID;

        if ($becamePaid || $isStillPaidAndValuesChanged) {
            $this->refreshSalesCache();
        }
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        if ($sale->payment_status === PaymentStatus::PAID) {
            $this->refreshSalesCache();
        }
    }

    /**
     * Helper para refrescar el caché usando la lógica solicitada
     */
    private function refreshSalesCache(): void
    {
        // Force cache refresh by forgetting the previous key before executing the remember
        Cache::forget('today_sales_stats');
        Cache::forget('monthly_sales_stats');
        Cache::forget('daily_sales_stats');

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
