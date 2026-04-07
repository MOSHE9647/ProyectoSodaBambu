<?php

namespace App\Actions\Sale;

use App\Models\Sale;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GetTodaySalesTotal
{
    public function execute(?string $date = null, bool $untilCurrentTime = false): float
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        
        // El modelo en el proyecto es Sale (singular) y usa el campo 'date' para la fecha de venta
        $query = Sale::whereDate('date', $targetDate)
            ->where('payment_status', PaymentStatus::PAID);

        // Filtramos por hora usando 'created_at' para la comparación de tendencia
        if ($untilCurrentTime) {
            $query->whereTime('created_at', '<=', Carbon::now()->toTimeString());
        }

        $salesTotal = (float) $query->sum('total');

        // Cacheamos solo el total real de hoy (sin filtro de hora)
        if (!$date || $targetDate->isToday()) {
            if (!$untilCurrentTime) {
                Cache::put('today_sales_total', $salesTotal, Carbon::now()->endOfDay());
            }
        }

        return $salesTotal;
    }
}