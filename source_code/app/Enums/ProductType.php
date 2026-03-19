<?php

namespace App\Enums;

enum ProductType: string
{
    case MERCHANDISE = 'merchandise';
    case DISH = 'dish';
    case DRINK = 'drink';
    case PACKAGED = 'packaged';

    /**
     * Label used for showing the Product Type name
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::MERCHANDISE => 'Mercadería',
            self::DISH => 'Platillo',
            self::DRINK => 'Bebida',
            self::PACKAGED => 'Empaquetado',
        };
    }

}
