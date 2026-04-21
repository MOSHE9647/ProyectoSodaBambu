<?php

namespace App\Http\Middleware;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegister;
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
            $hasOpenRegister = CashRegister::where('user_id', auth()->id())
                ->where('status', CashRegisterStatus::OPEN)
                ->exists();

            if (! $hasOpenRegister) {
                // Compartimos una variable con todas las vistas de la solicitud actual
                view()->share('showOpeningCashModal', true);
            }
        }

        return $next($request);
    }
}
