<?php

namespace App\Observers;

use App\Actions\Contract\GetActiveContractsCountAction;
use App\Actions\Contract\GetDailyMealServiceAction;
use App\Models\Contract;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Cache;

class ContractObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        protected GetActiveContractsCountAction $getActiveContractsCount,
        protected GetDailyMealServiceAction $getDailyMealServiceAction
    ) {}

    /**
     * Handle the Contract "created" event.
     */
    public function created(Contract $contract): void
    {
        $this->refreshContractsCache();
    }

    /**
     * Handle the Contract "updated" event.
     */
    public function updated(Contract $contract): void
    {
        $this->refreshContractsCache();
    }

    /**
     * Handle the Contract "deleted" event.
     */
    public function deleted(Contract $contract): void
    {
        $this->refreshContractsCache();
    }

    /**
     * Handle the Contract "restored" event.
     */
    public function restored(Contract $contract): void
    {
        $this->refreshContractsCache();
    }

    /**
     * Handle the Contract "force deleted" event.
     */
    public function forceDeleted(Contract $contract): void
    {
        $this->refreshContractsCache();
    }

    private function refreshContractsCache(): void
    {
        // Clear the cache when a contract is created, updated, or deleted.
        Cache::forget('active_contracts_count');
        Cache::forget('todays_meals');

        Cache::remember('active_contracts_count',
            now()->addMinutes(10),
            $this->getActiveContractsCount->execute(...)
        );

        Cache::remember('todays_meals',
            now()->addMinutes(10),
            $this->getDailyMealServiceAction->execute(...)
        );
    }
}
