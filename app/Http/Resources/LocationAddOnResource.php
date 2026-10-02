<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LocationAddOn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LocationAddOn */
class LocationAddOnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'add_on_id' => $this->add_on_id,
            'name' => $this->whenLoaded('addOn', fn () => $this->addOn->name),
            'icon' => $this->whenLoaded('addOn', fn () => $this->addOn->icon),
            'availability' => $this->availability->value,
            'price_cents' => $this->price_cents,
            'pricing_unit' => $this->pricing_unit->value,
        ];
    }
}
