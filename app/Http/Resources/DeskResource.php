<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Desk;
use App\Models\DeskAddOn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Desk */
class DeskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type->value,
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
            'rotation' => $this->rotation,
            'is_bookable' => $this->is_bookable,
            'assigned_user_id' => $this->assigned_user_id,
            'add_ons' => $this->whenLoaded('addOns', fn () => $this->addOns->map(fn (DeskAddOn $addOn): array => [
                'location_add_on_id' => $addOn->location_add_on_id,
                'availability' => $addOn->availability?->value,
                'price_cents' => $addOn->price_cents,
            ])->values()),
        ];
    }
}
