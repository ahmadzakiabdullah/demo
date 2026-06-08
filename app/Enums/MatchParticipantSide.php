<?php

namespace App\Enums;

enum MatchParticipantSide: string
{
    case Home = 'home';
    case Away = 'away';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}