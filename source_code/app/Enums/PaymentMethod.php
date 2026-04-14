<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case SINPE = 'sinpe';
    case CARD = 'card';

    public function label(): string
    {
        return match($this) {
            self::CASH  => 'Efectivo',
            self::SINPE => 'SINPE Móvil',
            self::CARD  => 'Tarjeta',
        };
    }
}