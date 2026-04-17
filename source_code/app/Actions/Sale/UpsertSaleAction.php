<?php

namespace App\Actions\Sale;

use App\Enums\PaymentStatus;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpsertSaleAction
{
    public function execute(array $saleValidatedData, array $saleDetailsData): Sale
    {
        return DB::transaction(function () use ($saleValidatedData, $saleDetailsData) {
            // 1. Limpiamos los datos para evitar pisar timestamps automáticos
            $saleData = Arr::except($saleValidatedData, ['id', 'sale_details', 'created_at', 'updated_at', 'deleted_at']);

            if (! isset($saleValidatedData['id'])) {
                $sale = Sale::create([
                    ...$saleData,
                    'user_id' => auth()->id() ?? throw new RuntimeException('No authenticated user found.'),
                    'invoice_number' => 'INV-TEMP',
                ]);

                $sale->update([
                    'invoice_number' => $this->formatInvoiceNumber($sale->id),
                ]);
            } else {
                $sale = Sale::withTrashed()->findOrFail($saleValidatedData['id']);
                $sale->update($saleData);
                if ($sale->trashed()) {
                    $sale->restore();
                }
            }

            // 2. Identificar IDs entrantes
            $incomingDetailIds = collect($saleDetailsData)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

            // 3. Eliminar (Soft Delete) los que no vienen en la petición
            $detailsToDelete = $sale->saleDetails()->whereNotIn('id', $incomingDetailIds)->get();
            foreach ($detailsToDelete as $detail) {
                match ($sale->payment_status) {
                    PaymentStatus::PAID => $detail->delete(),
                    PaymentStatus::PENDING => $detail->forceDelete(),
                    default => $detail->delete(),
                };
            }

            // 4. Crear o actualizar los detalles entrantes
            foreach ($saleDetailsData as $detailData) {
                $id = $detailData['id'] ?? null;
                $cleanData = Arr::except($detailData, ['created_at', 'updated_at', 'deleted_at']);

                $detail = $id
                    ? $sale->saleDetails()->withTrashed()->findOrFail($id)
                    : new SaleDetail(['sale_id' => $sale->id]);

                $detail->fill($cleanData);

                if ($detail->trashed()) {
                    $detail->restore();
                }

                $detail->save();
            }

            return $sale;
        }, 5);
    }

    private function formatInvoiceNumber(int $saleId): string
    {
        return 'INV-'.str_pad((string) $saleId, 10, '0', STR_PAD_LEFT);
    }
}
