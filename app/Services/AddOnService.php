<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AddOnAvailability;
use App\Enums\AddOnPricingUnit;
use App\Exceptions\BookingNotAllowedException;
use App\Models\BookingAddOn;
use App\Models\Desk;
use App\Models\DeskAddOn;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class AddOnService
{
    /**
     * The desk's optional extras matching the chosen add-on ids. Throws if any
     * chosen add-on is not attached to this desk as an optional extra.
     *
     * @param list<int> $addOnIds Catalogue add_ons.id values.
     * @return Collection<int, DeskAddOn>
     */
    public function optionalExtras(Desk $desk, array $addOnIds): Collection
    {
        $ids = array_values(array_unique($addOnIds));

        if ($ids === []) {
            return new Collection();
        }

        $extras = $this->activeFor($desk)
            ->whereHas('locationAddOn', fn (Builder $offer) => $offer->whereIn('add_on_id', $ids))
            ->get()
            ->filter(fn (DeskAddOn $extra): bool => $extra->effectiveAvailability() === AddOnAvailability::OPTIONAL)
            ->values();

        if ($extras->count() !== count($ids)) {
            throw BookingNotAllowedException::addOnUnavailable();
        }

        return $extras;
    }

    /**
     * Everything attached to the desk whose office offer is still active.
     *
     * @return Builder<DeskAddOn>
     */
    public function activeFor(Desk $desk): Builder
    {
        return DeskAddOn::query()
            ->where('desk_id', $desk->id)
            ->whereHas('locationAddOn', fn (Builder $offer) => $offer->active())
            ->with('locationAddOn.addOn');
    }

    /**
     * Unsaved line items for one booking. A booking never spans more than one
     * day, so per-day add-ons always count once.
     *
     * @param Collection<int, DeskAddOn> $extras
     * @return list<BookingAddOn>
     */
    public function linesFor(Collection $extras, CarbonImmutable $startsAt, CarbonImmutable $endsAt): array
    {
        return $extras->map(function (DeskAddOn $extra) use ($startsAt, $endsAt): BookingAddOn {
            $quantity = match ($extra->pricingUnit()) {
                AddOnPricingUnit::PER_BOOKING, AddOnPricingUnit::PER_DAY => 1,
                AddOnPricingUnit::PER_HOUR => max(1, (int) ceil($startsAt->diffInMinutes($endsAt) / 60)),
            };
            $unitPrice = $extra->effectivePriceCents() ?? 0;

            return new BookingAddOn([
                'add_on_id' => $extra->locationAddOn->add_on_id,
                'name' => $extra->locationAddOn->addOn->name,
                'pricing_unit' => $extra->pricingUnit(),
                'unit_price_cents' => $unitPrice,
                'quantity' => $quantity,
                'total_cents' => $unitPrice * $quantity,
            ]);
        })->values()->all();
    }
}
