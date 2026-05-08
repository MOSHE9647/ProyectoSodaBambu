<?php

namespace App\Actions\Finance;

use App\Enums\CashRegisterStatus;
use App\Enums\TransactionType;
use App\Models\CashRegister;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Transaction;
use Carbon\Carbon;

class RegisterTransactionAction
{
    public function execute(Payment $payment): Transaction
    {
        // Determine the type based on the payment origin
        // (Sale/Contract -> Income, Purchase/Payroll -> Expense)
        $type = match ($payment->origin_type) {
            Sale::class => TransactionType::INCOME,
            Contract::class => TransactionType::INCOME,
            Purchase::class => TransactionType::EXPENSE,
            // Payroll::class               => TransactionType::EXPENSE,
            default => throw new \InvalidArgumentException("Tipo de origen de pago no soportado: {$payment->origin_type}")
        };

        // Si el monto del pago es negativo, significa que es una devolución.
        // En este caso, invertimos el tipo de transacción para que la caja cuadre.
        $isRefund = $payment->amount < 0;
        if ($isRefund) {
            $type = ($type === TransactionType::INCOME) ? TransactionType::EXPENSE : TransactionType::INCOME;
        }

        $origin = $payment->origin; // This automatically loads the Sale, Purchase, etc. model.

        $message = match ($payment->origin_type) {
            Sale::class => "Pago de venta #{$origin?->invoice_number}",
            Purchase::class => "Compra a proveedor: {$origin?->supplier?->name}",
            Contract::class => ($isRefund ? 'Devolución de contrato' : 'Pago de contrato')." - Cliente: {$origin?->client?->first_name} {$origin?->client?->last_name}",

            // Hypothetically in the future:
            // Payroll::class  => "Pago de nómina - Colaborador: {$origin?->employee?->user?->name}",

            default => throw new \InvalidArgumentException("Tipo de origen de pago no soportado: {$payment->origin_type}")
        };

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
        $cashRegister = CashRegister::where('status', CashRegisterStatus::OPEN)
            ->whereBetween('opened_at', [$startUtc, $endUtc])
            ->first();

        if (! $cashRegister) {
            throw new \Exception('No hay una caja abierta para registrar la transacción.');
        }

        return Transaction::create([
            'payment_id' => $payment->id,
            'amount' => abs($payment->amount), // La transacción siempre guarda el monto absoluto
            'type' => $type,
            'concept' => $message,
            'cash_register_id' => $cashRegister?->id, // Associate with the open cash register if it exists
        ]);
    }
}
