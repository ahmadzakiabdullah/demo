<?php

namespace App\Enums;

enum TeamMemberRole: string
{
    case Member = 'member';
    case Captain = 'captain';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}