<?php

namespace App\Actions\Finance;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProcessPaymentAction
{
    public function __construct(
        protected RegisterTransactionAction $registerTransaction
    ) {}

    /**
     * Processes a payment for a specific origin (Sale, Purchase, etc.)
     *
     * @param  Model  $origin  The model that originates the payment (Sale, Purchase, etc.)
     * @param  array  $paymentData  Payment data: amount, method, reference, change_amount, date
     */
    public function execute(Model $origin, array $paymentData): Payment
    {
        return DB::transaction(function () use ($origin, $paymentData) {
            // register the payment
            $payment = Payment::create([
                'amount' => $paymentData['amount'],
                'method' => $paymentData['method'], // apps/Enums/PaymentMethod
                'change_amount' => $paymentData['change_amount'] ?? 0,
                'reference' => $paymentData['reference'] ?? null,
                'date' => $paymentData['date'] ?? now(),
                'origin_id' => $origin->id,
                'origin_type' => $origin->getMorphClass(),
            ]);

            // Generates the corresponding transaction record in the financial ledger
            $this->registerTransaction->execute($payment);

            return $payment;
        });
    }
}
