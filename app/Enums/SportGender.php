<?php

namespace App\Enums;

enum SportGender: string
{
    case Male = 'male';
    case Female = 'female';
    case Mixed = 'mixed';
    case Open = 'open';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}