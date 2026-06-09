<?php

namespace App\Enums;

enum ParticipantUnitLabel: string
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

    public function label(): string
    {
        return match ($this) {
            self::Faculty => 'Faculty',
            self::State => 'State',
            self::Country => 'Country',
            self::Club => 'Club',
            self::Other => 'Participant',
        };
    }
}