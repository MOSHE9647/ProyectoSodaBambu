<?php

namespace App\Observers;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        Cache::forget('monthly_sales_stats');
        Cache::forget('today_sales_stats'); 
        Cache::forget('daily_sales_stats');
    }
}
