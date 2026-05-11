<?php

namespace App\Actions\Contract;

use App\Models\Contract;
use Illuminate\Support\Facades\Cache;

class GetActiveContractsCountAction
{
    public function execute(): int
    {
        // Get only today's date in YYYY-MM-DD format for database comparison
        $today = now()->startOfDay()->toDateTimeString();

        $count = Contract::query()
            // Since the model uses SoftDeletes, Laravel automatically excludes "deleted/inactive" records
            ->where('start_date', '<=', $today) // Already started
            ->where('end_date', '>=', $today)   // Not yet expired
            ->count();

        Cache::forever('active_contracts_count', $count);

        return $count;
    }
}
