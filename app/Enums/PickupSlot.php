<?php

namespace App\Enums;

enum PickupSlot: string
{
    case MORNING = '09:00-12:00';
    case MIDDAY = '12:00-15:00';
    case AFTERNOON = '15:00-18:00';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return $this->value;
    }
}
