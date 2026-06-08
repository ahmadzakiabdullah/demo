<?php

namespace App\Enums;

enum MatchOfficialRole: string
{
    case Referee = 'referee';
    case Umpire = 'umpire';
    case Judge = 'judge';
    case Timekeeper = 'timekeeper';
    case TechnicalDelegate = 'technical_delegate';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}