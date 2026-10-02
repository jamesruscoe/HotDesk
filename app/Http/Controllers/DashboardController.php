<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\OrganizationResource;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly OrganizationService $organizationService) {}

    public function __invoke(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'organizations' => OrganizationResource::collection($this->organizationService->listFor($request->user())),
        ]);
    }
}
