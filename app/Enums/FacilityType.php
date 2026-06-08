<?php

namespace App\Enums;

enum FacilityType: string
{
    case Court = 'court';
    case Field = 'field';
    case Lane = 'lane';
    case Track = 'track';
    case Pool = 'pool';
    case Hall = 'hall';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}