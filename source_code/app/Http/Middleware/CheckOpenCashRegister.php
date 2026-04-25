<?php

namespace App\Http\Middleware;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOpenCashRegister
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            /**
             * Get the start and end of the current day in the local time zone (America/Costa_Rica - UTC-6).
             */
            $localTz = 'America/Costa_Rica';
            $todayStartLocal = Carbon::now($localTz)->startOfDay();
            $todayEndLocal = Carbon::now($localTz)->endOfDay();

            // Convert those limits to UTC to query the database
            $startUtc = $todayStartLocal->copy()->setTimezone('UTC');
            $endUtc = $todayEndLocal->copy()->setTimezone('UTC');

            // Check whether there is an open cash register created within the current day's range (local)
            $hasOpenRegister = CashRegister::where('status', CashRegisterStatus::OPEN)
                ->whereBetween('opened_at', [$startUtc, $endUtc])
                ->exists();

            if (! $hasOpenRegister) {
                // Share a variable with all views for the current request
                view()->share('showOpeningCashModal', true);
            }
        }

        return $next($request);
    }
}
