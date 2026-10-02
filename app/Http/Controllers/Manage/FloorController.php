<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manage;

use App\Exceptions\FloorPlanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreFloorRequest;
use App\Http\Requests\Manage\UpdateFloorPlanRequest;
use App\Http\Resources\FloorResource;
use App\Http\Resources\LocationAddOnResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserMinimalResource;
use App\Models\Floor;
use App\Models\Location;
use App\Models\Organization;
use App\Services\FloorService;
use App\Services\LocationService;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FloorController extends Controller
{
    public function __construct(
        private readonly FloorService $floorService,
        private readonly LocationService $locationService,
        private readonly OrganizationService $organizationService,
    ) {}

    public function store(StoreFloorRequest $request, Organization $organization, Location $location): RedirectResponse
    {
        $floor = $this->floorService->create($location, $request->validated(), $request->user());

        return redirect()
            ->route('manage.organizations.floors.edit', [$organization, $location, $floor])
            ->with('success', 'Floor created. Start drawing your floor plan.');
    }

    public function edit(Organization $organization, Location $location, Floor $floor): Response
    {
        return Inertia::render('Manage/Floors/Edit', [
            'organization' => new OrganizationResource($organization),
            'location' => new LocationResource($location),
            'floor' => new FloorResource($this->floorService->loadForEditing($floor)),
            'members' => UserMinimalResource::collection($this->organizationService->members($organization)),
            'addOnOffers' => LocationAddOnResource::collection($this->locationService->activeAddOnOffers($location)),
        ]);
    }

    public function update(UpdateFloorPlanRequest $request, Organization $organization, Location $location, Floor $floor): RedirectResponse
    {
        try {
            $this->floorService->saveLayout($floor, $request->validated(), $request->user());
        } catch (FloorPlanException $exception) {
            return back()->withErrors(['desks' => $exception->getMessage()]);
        }

        return back()->with('success', 'Floor plan saved.');
    }

    public function destroy(Request $request, Organization $organization, Location $location, Floor $floor): RedirectResponse
    {
        try {
            $this->floorService->delete($floor, $request->user());
        } catch (FloorPlanException $exception) {
            return back()->withErrors(['floor' => $exception->getMessage()]);
        }

        return redirect()
            ->route('manage.organizations.locations.show', [$organization, $location])
            ->with('success', 'Floor deleted.');
    }
}
