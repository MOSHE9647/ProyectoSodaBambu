<?php

namespace App\Actions\Contract;

use App\Enums\MealTime;
use App\Models\ContractDetail;
use Illuminate\Support\Collection;

class GetDailyMealServiceAction
{
    /**
     * Gets today's meal menu by determining the meal period
     * based on the current time.
     */
    public function execute(): Collection
    {
        $today = now()->startOfDay()->toDateTimeString();
        $currentHour = now()->format('H:i');

        // Cutoff point: before 10:30 AM use breakfast; otherwise, lunch.
        $activeMealTime = ($currentHour < '10:30') ? MealTime::BREAKFAST : MealTime::LUNCH;

        // Retrieve today's details for active contracts only.
        $details = ContractDetail::with(['contract.client', 'product'])
            ->whereHas('contract', function ($query) {
                // Ensure the contract is not soft deleted.
                $query->whereNull('deleted_at');
            })
            ->where('serve_date', $today)
            ->where('meal_time', $activeMealTime)
            ->get();

        // Group by contract to combine dish and drink in a single record.
        return $details->groupBy('contract_id')->map(function ($items) use ($activeMealTime) {
            $contract = $items->first()->contract;

            $dish = $items->firstWhere('product.type', 'dish')?->product->name ?? 'Sin platillo asignado';
            $drink = $items->firstWhere('product.type', 'drink')?->product->name ?? 'Sin bebida asignada';

            return (object) [
                'contract_id' => $contract->id,
                'business_name' => $contract->business_name,
                'client_name' => $contract->client->full_name ?? $contract->client->first_name,
                'portions' => $contract->portions_per_day,
                'meal_time_label' => $activeMealTime->label(),
                'dish' => $dish,
                'drink' => $drink,
            ];
        })->values();
    }
}
