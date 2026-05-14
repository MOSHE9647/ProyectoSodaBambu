<?php

namespace App\Enums;

enum MeasureUnit: string
{
    case KILOGRAMS = 'kg';
    case GRAMS = 'g';
    case LITERS = 'l';
    case MILLILITERS = 'ml';
    case UNITS = 'units';
    case PACKAGES = 'packs';

    public function label(): string
    {
        return match ($this) {
            self::KILOGRAMS => 'Kilogramos',
            self::GRAMS => 'Gramos',
            self::LITERS => 'Litros',
            self::MILLILITERS => 'Mililitros',
            self::UNITS => 'Unidades',
            self::PACKAGES => 'Paquetes',
        };
    }
}
