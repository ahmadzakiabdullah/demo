<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Published->value, self::Archived->value],
            self::Published => [self::Active->value, self::Draft->value, self::Archived->value],
            self::Active => [self::Completed->value, self::Published->value],
            self::Completed => [self::Archived->value, self::Active->value],
            self::Archived => [self::Draft->value],
        };
    }

    public function canTransitionTo(self|string $status): bool
    {
        $target = $status instanceof self ? $status->value : $status;

        return in_array($target, $this->allowedTransitions(), true) || $target === $this->value;
    }
}