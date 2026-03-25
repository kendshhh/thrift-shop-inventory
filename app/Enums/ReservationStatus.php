<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case OVERDUE = 'overdue';
    case EXPIRED = 'expired';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return true;
        }

        return match ($this) {
            self::PENDING => in_array($next, [self::COMPLETED, self::EXPIRED], true),
            self::COMPLETED => in_array($next, [self::OVERDUE], true),
            self::OVERDUE => in_array($next, [self::COMPLETED, self::EXPIRED], true),
            self::EXPIRED => false,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
