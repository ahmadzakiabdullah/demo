<?php

namespace App\Enums;

enum OrganizationType: string
{
    case Federation = 'federation';
    case University = 'university';
    case School = 'school';
    case Club = 'club';
    case Corporate = 'corporate';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}