<?php

namespace App\Enums;

enum MealTime: string
{
    case BREAKFAST = 'breakfast';
    case LUNCH = 'lunch';
    case DINNER = 'dinner';

    /**
     * Get all meal time values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $mealTime) => $mealTime->value, self::cases());
    }

    /**
     * Get the human-readable label for the meal time.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::BREAKFAST => 'Desayuno',
            self::LUNCH => 'Almuerzo',
            self::DINNER => 'Cena',
        };
    }
}
