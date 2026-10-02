<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Floor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Floor */
class FloorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'width' => $this->width,
            'height' => $this->height,
            'layout' => ['elements' => $this->layout['elements'] ?? []],
            'desks_count' => $this->whenCounted('desks'),
            'desks' => DeskResource::collection($this->whenLoaded('desks')),
        ];
    }
}
