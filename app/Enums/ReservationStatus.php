<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case READY_FOR_PICKUP = 'ready_for_pickup';
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

    /**
     * @return array<int, string>
     */
    public static function activeValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            [self::PENDING, self::READY_FOR_PICKUP, self::OVERDUE]
        );
    }

    /**
     * @return array<int, string>
     */
    public static function customerSelfServiceValues(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            [self::PENDING, self::OVERDUE]
        );
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return true;
        }

        return match ($this) {
            self::PENDING => in_array($next, [self::READY_FOR_PICKUP, self::COMPLETED, self::EXPIRED], true),
            self::READY_FOR_PICKUP => in_array($next, [self::COMPLETED, self::OVERDUE, self::EXPIRED], true),
            self::COMPLETED => false,
            self::OVERDUE => in_array($next, [self::READY_FOR_PICKUP, self::COMPLETED, self::EXPIRED], true),
            self::EXPIRED => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::READY_FOR_PICKUP => 'Ready for Pickup',
            default => ucfirst($this->value),
        };
    }
}
