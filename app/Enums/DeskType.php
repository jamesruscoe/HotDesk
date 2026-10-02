<?php

declare(strict_types=1);

namespace App\Enums;

enum DeskType: string
{
    case DESK = 'desk';
    case MEETING_ROOM = 'meeting_room';

    public function label(): string
    {
        return match ($this) {
            self::DESK => 'Desk',
            self::MEETING_ROOM => 'Meeting room',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
