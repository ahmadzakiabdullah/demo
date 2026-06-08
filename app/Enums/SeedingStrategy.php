<?php

namespace App\Enums;

enum SeedingStrategy: string
{
    case Name = 'name';
    case Random = 'random';
    case Manual = 'manual';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}