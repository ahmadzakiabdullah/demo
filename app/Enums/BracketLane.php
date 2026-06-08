<?php

namespace App\Enums;

enum BracketLane: string
{
    case Winners = 'winners';
    case Losers = 'losers';
    case GrandFinal = 'grand_final';
    case Swiss = 'swiss';
    case Ladder = 'ladder';
    case Knockout = 'knockout';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}