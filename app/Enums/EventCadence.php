<?php

namespace App\Enums;

enum EventCadence: string
{
    case Annual = 'annual';
    case Biennial = 'biennial';
    case Quadrennial = 'quadrennial';
    case OneOff = 'one_off';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Annual => 'Annual',
            self::Biennial => 'Biennial',
            self::Quadrennial => 'Quadrennial',
            self::OneOff => 'One-off',
        };
    }
}