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
            $this->handlePaymentDetails($contract, $paymentDetailsData);

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

            $detail = $id
                ? $contract->details()->withTrashed()->findOrFail($id)
                : new ContractDetail(['contract_id' => $contract->id]);

            $detail->fill($cleanData);

            if ($detail->trashed()) {
                $detail->restore();
            }

            $detail->save();
        }
    }

    private function handlePaymentDetails(Contract $contract, ?array $paymentDetailsData): void
    {
        // Si no vienen pagos nuevos (ej. el contrato se actualizó pero el saldo pendiente era 0), no hacemos nada.
        if (empty($paymentDetailsData)) {
            return;
        }

        // NOTA IMPORTANTE: A diferencia de las ventas tradicionales, aquí NO eliminamos los pagos
        // que no vengan en el Request. Esto preserva el historial de pagos anteriores cuando
        // el usuario solo está pagando una nueva "diferencia".

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
