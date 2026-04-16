<?php

namespace App\Actions\Sale;

use App\Enums\PaymentStatus;
use App\Models\Sale;
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
                $authenticatedUserId = auth()->id();
                if ($authenticatedUserId === null) {
                    throw new RuntimeException('Authenticated user is required to create a sale.');
                }

                $sale = Sale::create([
                    ...$saleData,
                    'user_id' => $authenticatedUserId,
                    'invoice_number' => 'INV-TEMP',
                ]);

                $sale->update([
                    'invoice_number' => $this->formatInvoiceNumber($sale->id),
                ]);
            } else {
                $sale = Sale::withTrashed()->updateOrCreate(
                    ['id' => $saleValidatedData['id']],
                    $saleData
                );

                if ($sale->trashed()) {
                    $sale->restore();
                }
            }

            // 2. Identificar IDs entrantes
            $incomingDetailIds = collect($saleDetailsData)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            // 3. Eliminar (Soft Delete) los que no vienen en la petición
            $detailsQuery = $sale->saleDetails()->whereNotIn('id', $incomingDetailIds);
            match ($sale->payment_status) {
                PaymentStatus::PAID => $detailsQuery->delete(),
                PaymentStatus::PENDING => $detailsQuery->forceDelete(),
                default => $detailsQuery->delete(),
            };

            // 4. Mapear datos para el upsert incluyendo 'deleted_at' => null para restaurar
            $saleDetails = collect($saleDetailsData)
                ->map(fn ($detail) => [
                    'id' => isset($detail['id']) ? (int) $detail['id'] : null,
                    'sale_id' => $sale->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_price' => $detail['unit_price'],
                    'applied_tax' => $detail['applied_tax'],
                    'sub_total' => $detail['sub_total'],
                    'deleted_at' => null, // Restaurar si existía como Soft Deleted
                    'updated_at' => now(),
                    'created_at' => $detail['created_at'] ?? now(),
                ])
                ->all();

            if (! empty($saleDetails)) {
                // Añadimos 'deleted_at' y 'updated_at' a los campos que deben actualizarse
                $fieldsToUpdate = ['product_id', 'quantity', 'unit_price', 'applied_tax', 'sub_total', 'deleted_at', 'updated_at'];
                $sale->saleDetails()->withTrashed()->upsert($saleDetails, ['id'], $fieldsToUpdate);
            }

            // 5. IMPORTANTE: Cargar las relaciones frescas antes de devolver
            return $sale;
        }, 5);
    }

    private function formatInvoiceNumber(int $saleId): string
    {
        return 'INV-'.str_pad((string) $saleId, 6, '0', STR_PAD_LEFT);
    }
}
