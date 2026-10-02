<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
class OrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'timezone' => $this->timezone,
            'role' => $this->whenPivotLoadedAs('membership', 'organization_user', fn () => $this->membership->role->value),
            'locations_count' => $this->whenCounted('locations'),
        ];
    }
}
