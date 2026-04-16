<?php

namespace App\Observers;

use App\Actions\Finance\ProcessPaymentAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Purchase;

class PurchaseObserver
{
    public function __construct(
        protected ProcessPaymentAction $processPayment
    ) {}

    /**
     * Handle the Purchase "created" event.
     */
    public function created(Purchase $purchase): void
    {
        if ($purchase->payment_status === PaymentStatus::PAID) {
            $this->processAutomaticPayment($purchase);
        }
    }

    /**
     * Handle the Purchase "updated" event.
     */
    public function updated(Purchase $purchase): void
    {
        $becamePaid = $purchase->wasChanged('payment_status') && $purchase->payment_status === PaymentStatus::PAID;

        if ($becamePaid) {
            $this->processAutomaticPayment($purchase);
        }
    }

    /**
     * Procesa el pago de la compra capturando datos del request o usando defaults.
     */
    private function processAutomaticPayment(Purchase $purchase): void
    {
        $methodValue = request()->input('payment_method', PaymentMethod::CASH->value);
        $method = PaymentMethod::tryFrom($methodValue) ?? PaymentMethod::CASH;

        $this->processPayment->execute($purchase, [
            'amount' => $purchase->total,
            'method' => $method,
            'reference' => request()->input('reference', $purchase->invoice_number),
            'date' => now(),
        ]);
    }
}
