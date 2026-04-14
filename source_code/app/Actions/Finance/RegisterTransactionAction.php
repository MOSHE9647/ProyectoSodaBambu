<?php

namespace App\Actions\Finance;

use App\Enums\TransactionType;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Transaction;

class RegisterTransactionAction
{
    public function execute(Payment $payment): Transaction
    {
        // Determine the type based on the payment origin
        // (Sale/Contract -> Income, Purchase/Payroll -> Expense)
        $type = match ($payment->origin_type) {
            Sale::class => TransactionType::INCOME,
            // Contract::class              => TransactionType::INCOME,
            Purchase::class => TransactionType::EXPENSE,
            // Payroll::class               => TransactionType::EXPENSE,
            default => throw new \InvalidArgumentException("Tipo de origen de pago no soportado: {$payment->origin_type}")
        };

        $origin = $payment->origin; // Esto carga automáticamente el modelo Sale, Purchase, etc.

        $message = match ($payment->origin_type) {
            Sale::class => "Pago de venta #{$origin->invoice_number}",
            Purchase::class => "Pago de compra a proveedor: {$origin->supplier->name}",

            // Hipotéticamente en el futuro:
            // Contract::class => "Pago de contrato - Cliente: {$origin->client->name}",
            // Payroll::class  => "Pago de nómina - Colaborador: {$origin->employee->user->name}",

            default => throw new \InvalidArgumentException("Tipo de origen de pago no soportado: {$payment->origin_type}")
        };

        return Transaction::create([
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'type' => $type,
            'concept' => $message,
        ]);
    }
}
