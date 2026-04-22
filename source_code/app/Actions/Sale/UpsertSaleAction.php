<?php

namespace App\Actions\Sale;

use App\Actions\Finance\ProcessPaymentAction;
use App\Actions\Finance\UpdatePaymentAction;
use App\Enums\PaymentStatus;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpsertSaleAction
{
    public function __construct(
        protected ProcessPaymentAction $createPayment,
        protected UpdatePaymentAction $updatePayment
    ) {}

    public function execute(array $saleData, array $saleDetailsData, ?array $salePaymentData): Sale
    {
        return DB::transaction(function () use ($saleData, $saleDetailsData, $salePaymentData) {
            // Clean the main sale data by removing non-fillable fields
            $saleId = $saleData['id'] ?? null;
            $saleData = Arr::except($saleData, ['id', 'created_at', 'updated_at', 'deleted_at']);

            if (! $saleId) {
                $sale = Sale::create([
                    ...$saleData,
                    'user_id' => auth()->id() ?? throw new RuntimeException('No authenticated user found.'),
                    'invoice_number' => 'FAC-TEMP',
                ]);

                $sale->update([
                    'invoice_number' => $this->formatInvoiceNumber($sale->id),
                ]);
            } else {
                $sale = Sale::withTrashed()->findOrFail($saleId);
                $sale->update($saleData);
                if ($sale->trashed()) {
                    $sale->restore();
                }
            }

            $this->handleSaleDetails($sale, $saleDetailsData);
            $this->handlePaymentDetails($sale, $salePaymentData);

            return $sale;
        }, 5);
    }

    private function formatInvoiceNumber(int $saleId): string
    {
        return 'FAC-'.str_pad((string) $saleId, 10, '0', STR_PAD_LEFT);
    }

    private function handleSaleDetails(Sale $sale, array $saleDetailsData): void
    {
        // Identify incoming detail IDs from the request
        $incomingDetailIds = collect($saleDetailsData)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        // Soft-delete existing details that are not in the incoming data
        $detailsToDelete = $sale->details()->whereNotIn('id', $incomingDetailIds)->get();
        foreach ($detailsToDelete as $detail) {
            match ($sale->payment_status) {
                PaymentStatus::PAID => $detail->delete(),
                PaymentStatus::PENDING => $detail->forceDelete(),
                default => $detail->delete(),
            };
        }

        // Create or update incoming details
        foreach ($saleDetailsData as $detailData) {
            $id = $detailData['id'] ?? null;
            $cleanData = Arr::except($detailData, ['created_at', 'updated_at', 'deleted_at']);

            $detail = $id
                ? $sale->details()->withTrashed()->findOrFail($id)
                : new SaleDetail(['sale_id' => $sale->id]);

            $detail->fill($cleanData);
            if ($detail->trashed()) {
                $detail->restore();
            }

            $detail->save();
        }
    }

    private function handlePaymentDetails(Sale $sale, ?array $salePaymentData): void
    {
        if ($salePaymentData === null) {
            return;
        }

        // Identify incoming payment IDs from the request
        $incomingPaymentIds = collect($salePaymentData)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)->all();

        // Soft-delete existing payments that are not in the incoming data
        $paymentsToDelete = $sale->payments()->whereNotIn('id', $incomingPaymentIds)->get();
        foreach ($paymentsToDelete as $payment) {
            $payment->transaction?->delete();
            $payment->delete();
        }

        // Create or update incoming payments
        foreach ($salePaymentData as $paymentData) {
            $paymentId = $paymentData['id'] ?? null;
            $cleanData = Arr::except($paymentData, ['id', 'created_at', 'updated_at', 'deleted_at']);

            match ($paymentId) {
                null => $this->createPayment->execute($sale, $cleanData),
                default => $this->updatePayment->execute(
                    $sale->payments()->findOrFail($paymentId),
                    $cleanData
                ),
            };
        }
    }
}
