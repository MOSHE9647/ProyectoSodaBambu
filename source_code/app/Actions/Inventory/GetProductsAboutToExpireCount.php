<?php

namespace App\Actions\Inventory;

use App\Models\PurchaseDetail;
use App\Models\Supply;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GetProductsAboutToExpireCount
{
    /**
     * Ejecuta el action para obtener el conteo de INSUMOS próximos a vencer.
     */
    public function execute(): int
    {
        $today = Carbon::now()->startOfDay();
        $expirationDateIn7Days = Carbon::now()->addDays(7)->endOfDay();

        // Filtramos por el tipo polimórfico de Insumos (Supply)
        $aboutToExpireCount = PurchaseDetail::where('purchasable_type', Supply::class)
            // Filtramos por fecha
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [$today, $expirationDateIn7Days])
            // Aseguramos que el insumo relacionado no esté eliminado (Soft Delete)
            ->whereHasMorph('purchasable', [Supply::class])
            ->count();

        Cache::forever('about_to_expire_count', $aboutToExpireCount);

        return $aboutToExpireCount;
    }
}