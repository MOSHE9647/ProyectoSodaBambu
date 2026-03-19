<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PARTIAL = 'partial';
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    /**
     * Label used for showing the Payment Status name
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PARTIAL => 'Parcial',
            self::PENDING => 'Pendiente',
            self::PAID => 'Completo',
            self::CANCELLED => 'Anulado',
        };
    }
}
