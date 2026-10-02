<?php

declare(strict_types=1);

namespace App\Http\Requests\Manage;

use App\Enums\AddOnAvailability;
use App\Enums\DeskType;
use App\Models\Desk;
use App\Models\Floor;
use App\Models\LocationAddOn;
use App\Models\Membership;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The whole floor plan in one payload: canvas size, drawn elements (walls,
 * rooms, labels) and the full list of desks. Desks without an id are new.
 */
class UpdateFloorPlanRequest extends FormRequest
{
    private const MAX_CANVAS = 10000;

    public function rules(): array
    {
        /** @var Floor $floor */
        $floor = $this->route('floor');
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'width' => ['required', 'integer', 'between:200,'.self::MAX_CANVAS],
            'height' => ['required', 'integer', 'between:200,'.self::MAX_CANVAS],

            'layout' => ['present', 'array'],
            'layout.elements' => ['present', 'array', 'max:2000'],
            'layout.elements.*.id' => ['required', 'string', 'max:64', 'distinct'],
            'layout.elements.*.type' => ['required', Rule::in(['wall', 'room', 'label'])],
            'layout.elements.*.points' => ['required_if:layout.elements.*.type,wall', 'array', 'min:4', 'max:400'],
            'layout.elements.*.points.*' => ['numeric', 'between:'.-self::MAX_CANVAS.','.self::MAX_CANVAS * 2],
            'layout.elements.*.thickness' => ['nullable', 'numeric', 'between:1,100'],
            'layout.elements.*.x' => ['nullable', 'numeric'],
            'layout.elements.*.y' => ['nullable', 'numeric'],
            'layout.elements.*.width' => ['nullable', 'numeric', 'min:1'],
            'layout.elements.*.height' => ['nullable', 'numeric', 'min:1'],
            'layout.elements.*.rotation' => ['nullable', 'numeric', 'between:-360,360'],
            'layout.elements.*.label' => ['nullable', 'string', 'max:120'],
            'layout.elements.*.text' => ['nullable', 'string', 'max:255'],
            'layout.elements.*.font_size' => ['nullable', 'numeric', 'between:6,200'],

            'desks' => ['present', 'array', 'max:2000'],
            'desks.*.id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists(Desk::getTableName(), 'id')->where('floor_id', $floor->id)->whereNull('deleted_at'),
            ],
            'desks.*.label' => ['required', 'string', 'max:40', 'distinct:ignore_case'],
            'desks.*.type' => ['required', Rule::enum(DeskType::class)],
            'desks.*.x' => ['required', 'numeric'],
            'desks.*.y' => ['required', 'numeric'],
            'desks.*.width' => ['required', 'numeric', 'between:10,2000'],
            'desks.*.height' => ['required', 'numeric', 'between:10,2000'],
            'desks.*.rotation' => ['nullable', 'numeric', 'between:-360,360'],
            'desks.*.is_bookable' => ['required', 'boolean'],
            'desks.*.assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists((new Membership())->getTable(), 'user_id')->where('organization_id', $organization->id),
            ],
            'desks.*.add_ons' => ['present', 'array'],
            'desks.*.add_ons.*.location_add_on_id' => [
                'required',
                'integer',
                Rule::exists(LocationAddOn::getTableName(), 'id')->where('location_id', $floor->location_id),
            ],
            'desks.*.add_ons.*.availability' => ['nullable', Rule::enum(AddOnAvailability::class)],
            'desks.*.add_ons.*.price_cents' => ['nullable', 'integer', 'min:0', 'max:10000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'desks.*.label.distinct' => 'Each desk needs a unique label on this floor.',
            'desks.*.label.required' => 'Every desk needs a label.',
        ];
    }
}
