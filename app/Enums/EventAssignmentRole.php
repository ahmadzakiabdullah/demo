<?php

namespace App\Enums;

enum EventAssignmentRole: string
{
    case EventOrganizer = 'event_organizer';
    case SportsManager = 'sports_manager';
    case TeamManager = 'team_manager';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}