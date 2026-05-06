<?php

namespace App\Enums;

enum ContractState: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case UPCOMING = 'upcoming';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::INACTIVE => 'Inactivo',
            self::UPCOMING => 'Próximo',
            self::EXPIRED => 'Expirado',
        };
    }
}
