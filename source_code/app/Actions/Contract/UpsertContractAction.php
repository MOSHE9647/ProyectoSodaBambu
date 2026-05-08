<?php

namespace App\Actions\Contract;

use App\Actions\Finance\ProcessPaymentAction;
use App\Actions\Finance\UpdatePaymentAction;
use App\Models\Contract;
use App\Models\ContractDetail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpsertContractAction
{
    public function __construct(
        protected ProcessPaymentAction $createPayment,
        protected UpdatePaymentAction $updatePayment
    ) {}

    public function execute(array $contractData, array $contractDetailsData, ?array $paymentDetailsData): Contract
    {
        return DB::transaction(function () use ($contractData, $contractDetailsData, $paymentDetailsData) {
            // Limpiamos los datos principales quitando campos no asignables y campos de control (como payment_status)
            $contractId = $contractData['id'] ?? null;
            $contractData = Arr::except($contractData, ['id', 'created_at', 'updated_at', 'deleted_at', 'payment_status']);

            if (! $contractId) {
                // Creación de un nuevo contrato
                $contract = Contract::create([
                    ...$contractData,
                    'user_id' => auth()->id() ?? throw new RuntimeException('No authenticated user found.'),
                ]);
            } else {
                // Actualización de un contrato existente
                $contract = Contract::withTrashed()->findOrFail($contractId);
                $contract->update($contractData);
                if ($contract->trashed()) {
                    $contract->restore();
                }
            }

            // Procesar relaciones
            $this->handleContractDetails($contract, $contractDetailsData);
            
            $this->processAutomaticRefundOrPayment($contract, $paymentDetailsData);

            return $contract;
        }, 5);
    }

    private function handleContractDetails(Contract $contract, array $contractDetailsData): void
    {
        // Identificar los IDs de los detalles que vienen en el request
        $incomingDetailIds = collect($contractDetailsData)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        // Eliminar (soft-delete) los detalles existentes que el usuario quitó de la tabla en el frontend
        $detailsToDelete = $contract->details()->whereNotIn('id', $incomingDetailIds)->get();
        foreach ($detailsToDelete as $detail) {
            $detail->delete();
        }

        // Crear o actualizar los detalles entrantes
        foreach ($contractDetailsData as $detailData) {
            $id = $detailData['id'] ?? null;
            $cleanData = Arr::except($detailData, ['id', 'created_at', 'updated_at', 'deleted_at']);

            // Buscamos un detalle existente por ID o por la combinación única (incluyendo eliminados)
            // Esto evita el error de Duplicate Entry al intentar crear algo que ya existe en Soft Deletes.
            $detail = $id 
                ? $contract->details()->withTrashed()->findOrFail($id)
                : $contract->details()->withTrashed()
                    ->where('product_id', $cleanData['product_id'])
                    ->where('meal_time', $cleanData['meal_time'] instanceof \App\Enums\MealTime ? $cleanData['meal_time']->value : $cleanData['meal_time'])
                    ->whereDate('serve_date', $cleanData['serve_date'])
                    ->first();

            if (! $detail) {
                $detail = new ContractDetail(['contract_id' => $contract->id]);
            }

            $detail->fill($cleanData);

            if ($detail->trashed()) {
                $detail->restore();
            }

            $detail->save();
        }
    }

    private function processAutomaticRefundOrPayment(Contract $contract, ?array $paymentDetailsData): void
    {
        $totalValue = (float) $contract->total_value;
        $totalPaid = (float) $contract->payments()->sum(DB::raw('amount - change_amount'));
        $pendingBalance = round($totalValue - $totalPaid, 2);

        // CASO 1: Hay pagos nuevos enviados desde el modal (Diferencia positiva)
        if (!empty($paymentDetailsData)) {
            $this->handlePaymentDetails($contract, $paymentDetailsData);
            return;
        }

        // CASO 2: El nuevo total es menor a lo pagado (Diferencia negativa = Devolución)
        if ($pendingBalance < 0) {
            $this->createPayment->execute($contract, [
                'amount' => $pendingBalance, // Se envía negativo (ej: -5000)
                'method' => \App\Enums\PaymentMethod::CASH->value, // Las devoluciones suelen ser en efectivo
                'change_amount' => 0,
                'reference' => 'Devolución por ajuste de valor de contrato',
                'date' => now(),
            ]);
        }
    }

    private function handlePaymentDetails(Contract $contract, array $paymentDetailsData): void
    {
        // Crear o actualizar pagos entrantes
        foreach ($paymentDetailsData as $paymentData) {
            $paymentId = $paymentData['id'] ?? null;
            $cleanData = Arr::except($paymentData, ['id', 'created_at', 'updated_at', 'deleted_at']);

            match ($paymentId) {
                null => $this->createPayment->execute($contract, $cleanData),
                default => $this->updatePayment->execute(
                    $contract->payments()->findOrFail($paymentId),
                    $cleanData
                ),
            };
        }
    }
}
