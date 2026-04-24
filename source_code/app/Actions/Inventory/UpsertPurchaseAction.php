<?php

namespace App\Actions\Inventory;

use App\Actions\Finance\ProcessPaymentAction;
use App\Actions\Finance\UpdatePaymentAction;
use App\Enums\PaymentStatus;
use App\Models\Purchase;
use DB;
use Illuminate\Support\Arr;
use RuntimeException;

class UpsertPurchaseAction
{
    public function __construct(
        protected ProcessPaymentAction $createPayment,
        protected UpdatePaymentAction $updatePayment
    ) {}

    public function execute(array $purchaseData, array $purchaseDetailsData, ?array $purchasePaymentData): Purchase
    {
        return DB::transaction(function () use ($purchaseData, $purchaseDetailsData, $purchasePaymentData) {
            // Clean the main purchase data by removing non-fillable fields
            $purchaseId = $purchaseData['id'] ?? null;
            $purchaseData = Arr::except($purchaseData, ['id', 'created_at', 'updated_at', 'deleted_at']); 

            if (! $purchaseId) {
                $purchase = Purchase::create([
                    ...$purchaseData,
                    'user_id' => auth()->id() ?? throw new RuntimeException('No authenticated user found.'),
                ]);
            } else {
                $purchase = Purchase::withTrashed()->findOrFail($purchaseId);
                $purchase->update($purchaseData);
                if ($purchase->trashed()) {
                    $purchase->restore();
                }
            }

            $this->handlePurchaseDetails($purchase, $purchaseDetailsData);
            $this->handlePaymentDetails($purchase, $purchasePaymentData);

            return $purchase;
        }, 5);
    }

    /**
     * Handle the upsert (update or insert) and deletion of purchase details for a given purchase.
     *
     * This method synchronizes the purchase details with the provided data:
     * - Deletes details that are not present in the incoming data (soft or force delete depending on payment status).
     * - Updates existing details or creates new ones as needed.
     *
     * @param Purchase $purchase The purchase model instance to update details for.
     * @param array $purchaseDetailsData Array of detail data to upsert.
     * @return void
     */
    private function handlePurchaseDetails(Purchase $purchase, array $purchaseDetailsData): void
    {
        // Collect IDs of incoming details from the request data
        $incomingDetailIds = collect($purchaseDetailsData)
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();

        // Find and delete details that are not present in the incoming data
        $detailsToDelete = $purchase->details()->whereNotIn('id', $incomingDetailIds)->get();
        foreach ($detailsToDelete as $detail) {
            // Delete or force delete based on payment status
            match ($purchase->payment_status) {
                PaymentStatus::PAID => $detail->delete(),
                PaymentStatus::PENDING => $detail->forceDelete(),
                default => $detail->delete(),
            };
        }

        // Upsert (update or insert) incoming details
        foreach ($purchaseDetailsData as $detailData) {
            $detailId = $detailData['id'] ?? null;

            // Remove non-fillable fields
            $detailData = Arr::except($detailData, ['created_at', 'updated_at', 'deleted_at']);

            // Find existing detail or create a new one
            $detail = $detailId
                ? $purchase->details()->withTrashed()->findOrFail($detailId)
                : $purchase->make(['purchase_id' => $purchase->id]);

            $detail->fill($detailData);
            // Restore if previously soft deleted
            if ($detail->trashed()) {
                $detail->restore();
            }

            $detail->save();
        }
    }

    /**
     * Handle the upsert (update or insert) and deletion of payment details for a given purchase.
     *
     * This method synchronizes the purchase payments with the provided data:
     * - Deletes payments that are not present in the incoming data (including their related transactions).
     * - Updates existing payments or creates new ones as needed.
     *
     * @param Purchase $purchase The purchase model instance to update payments for.
     * @param array|null $purchasePaymentData Array of payment data to upsert, or null if no payments are provided.
     * @return void
     */
    private function handlePaymentDetails(Purchase $purchase, ?array $purchasePaymentData): void
    {
        if ($purchasePaymentData === null) {
            return;
        }

        // Collect IDs of incoming payments from the request data
        $incomingPaymentIds = collect($purchasePaymentData)
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();

        // Find and delete payments that are not present in the incoming data
        $paymentsToDelete = $purchase->payments()->whereNotIn('id', $incomingPaymentIds)->get();
        foreach ($paymentsToDelete as $payment) {
            $payment->transaction?->delete();
            $payment->delete();
        }

        // Upsert (update or insert) incoming payments
        foreach ($purchasePaymentData as $paymentData) {
            $paymentId = $paymentData['id'] ?? null;

            // Remove non-fillable fields
            $paymentData = Arr::except($paymentData, ['id', 'created_at', 'updated_at', 'deleted_at']);

            match ($paymentId) {
                null => $this->createPayment->execute($purchase, $paymentData),
                default => $this->updatePayment->execute(
                    $purchase->payments()->findOrFail($paymentId),
                    $paymentData
                ),
            };
        }
    }
}