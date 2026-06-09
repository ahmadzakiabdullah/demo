<?php

namespace App\Enums;

enum EventParticipantType: string
{
    case Faculty = 'faculty';
    case State = 'state';
    case Country = 'country';
    case Club = 'club';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}