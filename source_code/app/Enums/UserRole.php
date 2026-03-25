<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';
    case GUEST = 'guest';

    /**
     * Label used for showing the Role name
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::EMPLOYEE => 'Colaborador',
            self::GUEST => 'Invitado',
        };
    }
}
