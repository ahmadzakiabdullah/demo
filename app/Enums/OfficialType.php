<?php

namespace App\Enums;

enum OfficialType: string
{
    case Referee = 'referee';
    case Judge = 'judge';
    case TechnicalOfficer = 'technical_officer';
    case Timekeeper = 'timekeeper';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}