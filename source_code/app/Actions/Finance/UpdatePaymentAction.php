<?php

namespace App\Actions\Finance;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class UpdatePaymentAction
{
    /**
     * Updates an existing payment and synchronizes the corresponding transaction record.
     */
    public function execute(Payment $payment, array $paymentData): Payment
    {
        return DB::transaction(function () use ($payment, $paymentData) {
            // Update the payment record with new data
            $payment->update([
                'amount' => $paymentData['amount'],
                'method' => $paymentData['method'],
                'change_amount' => $paymentData['change_amount'] ?? 0,
                'reference' => $paymentData['reference'] ?? null,
                'date' => $paymentData['date'] ?? $payment->date,
            ]);

            // Sync with the corresponding transaction record
            if ($payment->transaction) {
                $payment->transaction->update([
                    'amount' => $payment->amount,
                ]);
            }

            return $payment;
        });
    }
}
