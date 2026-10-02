<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case GUEST = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::MEMBER => 'Member',
            self::GUEST => 'Guest',
        };
    }

    public function canManage(): bool
    {
        return match ($this) {
            self::OWNER, self::ADMIN => true,
            self::MEMBER, self::GUEST => false,
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
