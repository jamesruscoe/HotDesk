<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A user's membership of an organization (the organization_user pivot).
 */
class Membership extends Pivot
{
    public $incrementing = true;

    protected $table = 'organization_user';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => OrganizationRole::class,
        ];
    }
}
