<?php

namespace App\Enums;

enum AppealStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Upheld = 'upheld';
    case Overturned = 'overturned';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview], true);
    }
}