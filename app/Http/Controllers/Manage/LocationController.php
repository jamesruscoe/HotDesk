<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreLocationRequest;
use App\Http\Resources\FloorResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\OrganizationResource;
use App\Models\Location;
use App\Models\Organization;
use App\Services\FloorService;
use App\Services\LocationService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationService $locationService,
        private readonly FloorService $floorService,
    ) {}

    public function create(Organization $organization): Response
    {
        return Inertia::render('Manage/Locations/Create', [
            'organization' => new OrganizationResource($organization),
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function store(StoreLocationRequest $request, Organization $organization): RedirectResponse
    {
        $location = $this->locationService->create($organization, $request->validated(), $request->user());

        return redirect()
            ->route('manage.organizations.locations.show', [$organization, $location])
            ->with('success', 'Office created.');
    }

    public function show(Organization $organization, Location $location): Response
    {
        return Inertia::render('Manage/Locations/Show', [
            'organization' => new OrganizationResource($organization),
            'location' => new LocationResource($location),
            'floors' => FloorResource::collection($this->floorService->listFor($location)),
        ]);
    }
}
