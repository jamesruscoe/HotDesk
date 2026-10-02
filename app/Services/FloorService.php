<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FloorPlanException;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\Floor;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class FloorService
{
    /**
     * @return Collection<int, Floor>
     */
    public function listFor(Location $location): Collection
    {
        // Desks are loaded for the plan thumbnails on floor cards.
        return $location->floors()
            ->withCount('desks')
            ->with('desks:id,floor_id,label,type,x,y,width,height,rotation,is_bookable,assigned_user_id')
            ->orderBy('level')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array{name: string, level: int} $data
     */
    public function create(Location $location, array $data, User $actor): Floor
    {
        return $location->floors()->create([
            'name' => $data['name'],
            'level' => $data['level'],
            'layout' => ['elements' => []],
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    public function loadForEditing(Floor $floor): Floor
    {
        return $floor->load(['desks' => fn ($query) => $query->orderBy('id'), 'desks.addOns']);
    }

    /**
     * Saves the drawn structure and syncs the floor's desks: rows with an id
     * are updated, rows without are created, and desks missing from the
     * payload are removed unless they have upcoming bookings.
     *
     * @param array{
     *     width: int,
     *     height: int,
     *     layout: array{elements: list<array<string, mixed>>},
     *     desks: list<array<string, mixed>>,
     * } $data
     */
    public function saveLayout(Floor $floor, array $data, User $actor): Floor
    {
        return DB::transaction(function () use ($floor, $data, $actor): Floor {
            $floor->update([
                'width' => $data['width'],
                'height' => $data['height'],
                'layout' => ['elements' => $data['layout']['elements'] ?? []],
                'updated_by' => $actor->id,
            ]);

            $existing = $floor->desks()->get()->keyBy('id');
            $keptIds = [];

            foreach ($data['desks'] as $deskData) {
                $desk = $this->upsertDesk($floor, $existing, $deskData, $actor);
                $this->syncDeskAddOns($desk, $deskData['add_ons'] ?? []);
                $keptIds[] = $desk->id;
            }

            $this->removeDesks($existing->except($keptIds), $actor);

            return $floor;
        });
    }

    public function delete(Floor $floor, User $actor): void
    {
        $hasUpcoming = Booking::query()
            ->whereIn('desk_id', $floor->desks()->select('id'))
            ->holdingDesk()
            ->where('ends_at', '>', now())
            ->exists();

        if ($hasUpcoming) {
            throw FloorPlanException::floorHasUpcomingBookings();
        }

        DB::transaction(function () use ($floor, $actor): void {
            $floor->desks()->update(['deleted_by' => $actor->id, 'deleted_at' => now()]);
            $floor->update(['deleted_by' => $actor->id]);
            $floor->delete();
        });
    }

    /**
     * @param Collection<int, Desk> $existing
     * @param array<string, mixed> $deskData
     */
    private function upsertDesk(Floor $floor, Collection $existing, array $deskData, User $actor): Desk
    {
        $attributes = [
            'label' => $deskData['label'],
            'type' => $deskData['type'],
            'x' => $deskData['x'],
            'y' => $deskData['y'],
            'width' => $deskData['width'],
            'height' => $deskData['height'],
            'rotation' => $deskData['rotation'] ?? 0,
            'is_bookable' => $deskData['is_bookable'],
            'assigned_user_id' => $deskData['assigned_user_id'] ?? null,
            'updated_by' => $actor->id,
        ];

        $id = $deskData['id'] ?? null;

        if ($id !== null && $existing->has($id)) {
            $desk = $existing->get($id);
            $desk->update($attributes);

            return $desk;
        }

        return $floor->desks()->create([...$attributes, 'created_by' => $actor->id]);
    }

    /**
     * @param list<array{location_add_on_id: int, availability?: string|null, price_cents?: int|null}> $addOns
     */
    private function syncDeskAddOns(Desk $desk, array $addOns): void
    {
        $offerIds = array_map(fn (array $addOn): int => (int) $addOn['location_add_on_id'], $addOns);

        $desk->addOns()->whereNotIn('location_add_on_id', $offerIds)->delete();

        foreach ($addOns as $addOn) {
            $desk->addOns()->updateOrCreate(
                ['location_add_on_id' => $addOn['location_add_on_id']],
                [
                    'availability' => $addOn['availability'] ?? null,
                    'price_cents' => $addOn['price_cents'] ?? null,
                ],
            );
        }
    }

    /**
     * @param Collection<int, Desk> $desks
     */
    private function removeDesks(Collection $desks, User $actor): void
    {
        if ($desks->isEmpty()) {
            return;
        }

        $bookedDeskIds = Booking::query()
            ->whereIn('desk_id', $desks->modelKeys())
            ->holdingDesk()
            ->where('ends_at', '>', now())
            ->distinct()
            ->pluck('desk_id')
            ->all();

        if ($bookedDeskIds !== []) {
            throw FloorPlanException::desksHaveUpcomingBookings(
                $desks->only($bookedDeskIds)->pluck('label')->sort()->values()->all(),
            );
        }

        foreach ($desks as $desk) {
            $desk->update(['deleted_by' => $actor->id]);
            $desk->delete();
        }
    }
}
