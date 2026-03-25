<?php

namespace App\Enums;

enum ItemStatus: string
{
    case ACTIVE = 'active';
    case OUT_OF_STOCK = 'out_of_stock';
    case ARCHIVED = 'archived';

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
            self::ACTIVE => 'Active',
            self::OUT_OF_STOCK => 'Out of Stock',
            self::ARCHIVED => 'Archived',
        };
    }
}
