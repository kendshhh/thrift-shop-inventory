<?php

namespace App\Enums;

enum ItemCondition: string
{
    case NEW = 'new';
    case GENTLY_USED = 'gently_used';
    case WORN = 'worn';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::GENTLY_USED => 'Gently Used',
            self::WORN => 'Worn',
        };
    }
}
