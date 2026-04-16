<?php

namespace App\Enums;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    /**
     * Label used for showing the Transaction Type name
     */
    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Ingreso',
            self::EXPENSE => 'Gasto',
        };
    }
}
