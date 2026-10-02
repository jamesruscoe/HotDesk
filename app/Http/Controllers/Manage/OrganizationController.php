<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manage\StoreOrganizationRequest;
use App\Http\Resources\LocationResource;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\LocationService;
use App\Services\OrganizationService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
        private readonly LocationService $locationService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Manage/Organizations/Index', [
            'organizations' => OrganizationResource::collection($this->organizationService->listFor($request->user())),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Manage/Organizations/Create', [
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = $this->organizationService->create($request->validated(), $request->user());

        return redirect()->route('manage.organizations.show', $organization)->with('success', 'Organization created.');
    }

    public function show(Organization $organization): Response
    {
        return Inertia::render('Manage/Organizations/Show', [
            'organization' => new OrganizationResource($organization),
            'locations' => LocationResource::collection($this->locationService->listFor($organization)),
        ]);
    }
}
