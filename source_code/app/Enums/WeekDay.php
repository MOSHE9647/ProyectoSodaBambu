<?php

namespace App\Enums;

enum WeekDay: string
{
    case MONDAY = 'Monday';
    case TUESDAY = 'Tuesday';
    case WEDNESDAY = 'Wednesday';
    case THURSDAY = 'Thursday';
    case FRIDAY = 'Friday';
    case SATURDAY = 'Saturday';
    case SUNDAY = 'Sunday';

    /**
     * Get an array of all week day names.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(fn (self $day) => $day->value, self::cases());
    }

    /**
     * Label used for showing the Week Day name
     */
    public function label(): string
    {
        return match ($this) {
            self::MONDAY => 'Lunes',
            self::TUESDAY => 'Martes',
            self::WEDNESDAY => 'Miércoles',
            self::THURSDAY => 'Jueves',
            self::FRIDAY => 'Viernes',
            self::SATURDAY => 'Sábado',
            self::SUNDAY => 'Domingo',
        };
    }

    /**
     * Short label for showing the Week Day name in compact spaces
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::MONDAY => 'Lun',
            self::TUESDAY => 'Mar',
            self::WEDNESDAY => 'Mié',
            self::THURSDAY => 'Jue',
            self::FRIDAY => 'Vie',
            self::SATURDAY => 'Sáb',
            self::SUNDAY => 'Dom',
        };
    }
}
