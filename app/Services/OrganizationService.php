<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class OrganizationService
{
    /**
     * @return Collection<int, Organization>
     */
    public function listFor(User $user): Collection
    {
        return $user->organizations()->withCount('locations')->orderBy('name')->get();
    }

    /**
     * @param array{name: string, timezone: string} $data
     */
    public function create(array $data, User $actor): Organization
    {
        return DB::transaction(function () use ($data, $actor): Organization {
            $organization = Organization::query()->create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'timezone' => $data['timezone'],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $organization->members()->attach($actor, ['role' => OrganizationRole::OWNER->value]);

            return $organization;
        });
    }

    /**
     * Members for pickers such as desk assignment.
     *
     * @return Collection<int, User>
     */
    public function members(Organization $organization): Collection
    {
        return $organization->members()->orderBy('name')->get(['users.id', 'users.name', 'users.email']);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;

        while (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
