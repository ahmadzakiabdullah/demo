<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => in_array($status, [self::Submitted, self::Rejected], true),
            self::Submitted => in_array($status, [self::Verified, self::Approved, self::Rejected], true),
            self::Verified => in_array($status, [self::Approved, self::Rejected], true),
            self::Approved, self::Rejected => false,
        };
    }
}