<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Published = 'published';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}