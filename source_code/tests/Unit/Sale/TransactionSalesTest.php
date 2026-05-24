<?php

use App\Actions\Sale\GetDailySalesDataAction;
use App\Actions\Sale\GetMonthlySalesDataAction;
use App\Enums\CashRegisterStatus;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\Transaction;
use App\Observers\TransactionObserver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// ─── Helpers ───────────────────────────────────────────────────────────────

function makeTransactionIds(): array
{
    $paymentId = Payment::factory()->create([
        'amount' => 1000,
        'method' => PaymentMethod::CASH,
        'date' => now(),
    ])->id;

    $cashRegisterId = CashRegister::factory()->create([
        'opening_balance' => 0,
        'status' => CashRegisterStatus::OPEN,
        'opened_at' => now(),
    ])->id;

    return [$paymentId, $cashRegisterId];
}

function makeIncome(int $amount, string $createdAt): void
{
    [$paymentId, $cashRegisterId] = makeTransactionIds();

    // Convertir de CR (UTC-6) a UTC para que la Action lo encuentre
    $utcDate = Carbon::parse($createdAt, 'America/Costa_Rica')->timezone('UTC')->toDateTimeString();

    DB::table('transactions')->insert([
        'amount' => $amount,
        'type' => TransactionType::INCOME->value,
        'concept' => 'test',
        'payment_id' => $paymentId,
        'cash_register_id' => $cashRegisterId,
        'created_at' => $utcDate,
        'updated_at' => $utcDate,
    ]);
}

function makeExpense(int $amount, string $createdAt): void
{
    [$paymentId, $cashRegisterId] = makeTransactionIds();

    $utcDate = Carbon::parse($createdAt, 'America/Costa_Rica')->timezone('UTC')->toDateTimeString();

    DB::table('transactions')->insert([
        'amount' => $amount,
        'type' => TransactionType::EXPENSE->value,
        'concept' => 'test',
        'payment_id' => $paymentId,
        'cash_register_id' => $cashRegisterId,
        'created_at' => $utcDate,
        'updated_at' => $utcDate,
    ]);
}

// ─── CA-01 y CA-02: Gráfico mensual (admin) ───────────────────────────────

test('CA-01 - gráfico mensual suma ventas y contratos del mes y excluye meses anteriores', function () {
    $thisMonth = now('America/Costa_Rica')->startOfMonth()->toDateTimeString();
    $lastMonth = now('America/Costa_Rica')->subMonth()->startOfMonth()->toDateTimeString();

    makeIncome(10000, $thisMonth);
    makeIncome(5000, $thisMonth);
    makeIncome(7000, $lastMonth);

    $result = app(GetMonthlySalesDataAction::class)->execute();

    expect($result['monthlyTotal'])->toBe(15000);
});

test('CA-02 - gráfico mensual excluye egresos', function () {
    $thisMonth = now('America/Costa_Rica')->startOfMonth()->toDateTimeString();

    makeIncome(8000, $thisMonth);
    makeExpense(99000, $thisMonth);

    $result = app(GetMonthlySalesDataAction::class)->execute();

    expect($result['monthlyTotal'])->toBe(8000);
});

test('CA-03 - gráfico diario suma ventas y contratos del día y excluye días anteriores', function () {
    Carbon::setTestNow(Carbon::create(2026, 5, 24, 16, 0, 0, 'UTC'));

    try {
        $today = now('America/Costa_Rica')->setTime(10, 0)->toDateTimeString();
        $yesterday = now('America/Costa_Rica')->subDay()->setTime(10, 0)->toDateTimeString();

        makeIncome(4000, $today);
        makeIncome(3000, $today);
        makeIncome(2000, $yesterday);

        $result = app(GetDailySalesDataAction::class)->execute();

        expect($result['dailyTotal'])->toBe(7000);
    } finally {
        Carbon::setTestNow();
    }
});

test('CA-04 - gráfico diario excluye egresos', function () {
    Carbon::setTestNow(Carbon::create(2026, 5, 24, 16, 0, 0, 'UTC'));

    try {
        $today = now('America/Costa_Rica')->setTime(10, 0)->toDateTimeString();

        makeIncome(5000, $today);
        makeExpense(99000, $today);

        $result = app(GetDailySalesDataAction::class)->execute();

        expect($result['dailyTotal'])->toBe(5000);
    } finally {
        Carbon::setTestNow();
    }
});

// ─── CA-05: Invalidación de caché ─────────────────────────────────────────

test('CA-05 - crear una transacción invalida los tres caches de estadísticas', function () {
    Cache::put('monthly_sales_stats', 'monthly', now()->addHour());
    Cache::put('today_sales_stats', 'today', now()->addHour());
    Cache::put('daily_sales_stats', 'daily', now()->addHour());

    $transaction = new Transaction([
        'amount' => 1000,
        'type' => TransactionType::INCOME,
        'concept' => 'test',
    ]);

    (new TransactionObserver)->created($transaction);

    expect(Cache::has('monthly_sales_stats'))->toBeFalse()
        ->and(Cache::has('today_sales_stats'))->toBeFalse()
        ->and(Cache::has('daily_sales_stats'))->toBeFalse();
});
