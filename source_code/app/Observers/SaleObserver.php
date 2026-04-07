<?php

namespace App\Observers;

use App\Actions\Sale\GetTodaySalesTotal;
use App\Models\Sale;

class SaleObserver
{
    public function __construct(protected GetTodaySalesTotal $getTodaySalesTotal)
    {
    }

    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        $this->getTodaySalesTotal->execute();
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        // Solo recalculamos si cambió el total, el estado de pago o la fecha
        if ($sale->wasChanged(['total', 'payment_status', 'date'])) {
            $this->getTodaySalesTotal->execute();
        }
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        $this->getTodaySalesTotal->execute();
    }

    /**
     * Handle the Sale "restored" event.
     */
    public function restored(Sale $sale): void
    {
        $this->getTodaySalesTotal->execute();
    }
}