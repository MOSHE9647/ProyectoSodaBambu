<?php

namespace App\Services\Receipt;

use App\Contracts\Receipable;

/**
 * ReceiptBuilder - Construye datos genéricos para recibos.
 *
 * Este servicio toma un modelo Receipable y extrae todos los datos necesarios
 * para generar un recibo, de forma agnóstica al modelo específico (Sale, Contract, etc).
 */
class ReceiptBuilder
{
    public function __construct(
        private Receipable $receipable,
    ) {}

    /**
     * Construye los datos completos para un recibo.
     *
     * @return array Datos listos para pasar al frontend
     */
    public function build(): array
    {
        $items = $this->getFormattedItems();
        $subtotal = $this->getSubtotal($items);
        $taxTotal = $this->getTaxTotal($items);
        $total = $this->receipable->getReceiptTotal();
        $payments = $this->receipable->getReceiptPayments();
        $totalTendered = collect($payments)->sum('amount');
        $changeAmount = $totalTendered - $total;

        return [
            'receipt_number' => $this->receipable->getReceiptNumber(),
            'date' => $this->receipable->getReceiptDate(),
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'model_type' => $this->receipable::class,
            'payments' => $payments,
            'change_amount' => $changeAmount,
            'total_tendered' => $totalTendered,
        ];
    }

    /**
     * Obtiene los items formateados con todos los campos necesarios.
     *
     * @return array Items con formato consistente
     */
    private function getFormattedItems(): array
    {
        $items = $this->receipable->getReceiptItems();

        return collect($items)->map(function ($item) {
            return [
                'name' => $item['name'] ?? 'Producto desconocido',
                'quantity' => (int) ($item['quantity'] ?? 0),
                'unit_price' => (int) ($item['unit_price'] ?? 0),
                'sub_total' => (int) ($item['sub_total'] ?? 0),
                'applied_tax' => (int) ($item['applied_tax'] ?? 0),
                'tax_amount' => $this->calculateTaxAmount(
                    (int) ($item['sub_total'] ?? 0),
                    (int) ($item['applied_tax'] ?? 0)
                ),
                'total' => $this->calculateItemTotal(
                    (int) ($item['sub_total'] ?? 0),
                    (int) ($item['applied_tax'] ?? 0)
                ),
            ];
        })->toArray();
    }

    /**
     * Calcula el monto de impuesto para un item.
     *
     * @param  int  $subtotal  El subtotal del item
     * @param  int  $taxPercentage  El porcentaje de impuesto (ej: 13 para 13%)
     * @return int El monto de impuesto
     */
    private function calculateTaxAmount(int $subtotal, int $taxPercentage): int
    {
        if ($taxPercentage <= 0) {
            return 0;
        }

        return (int) round($subtotal * ($taxPercentage / 100));
    }

    /**
     * Calcula el total de un item (subtotal + tax).
     *
     * @param  int  $subtotal  El subtotal
     * @param  int  $taxPercentage  El porcentaje de impuesto
     * @return int El total
     */
    private function calculateItemTotal(int $subtotal, int $taxPercentage): int
    {
        $taxAmount = $this->calculateTaxAmount($subtotal, $taxPercentage);

        return $subtotal + $taxAmount;
    }

    /**
     * Calcula el subtotal sumando todos los items.
     *
     * @param  array  $items  Los items formateados
     * @return int El subtotal total
     */
    private function getSubtotal(array $items): int
    {
        return (int) collect($items)->sum('sub_total');
    }

    /**
     * Calcula el total de impuestos sumando todos los items.
     *
     * @param  array  $items  Los items formateados
     * @return int El total de impuestos
     */
    private function getTaxTotal(array $items): int
    {
        return (int) collect($items)->sum('tax_amount');
    }
}
