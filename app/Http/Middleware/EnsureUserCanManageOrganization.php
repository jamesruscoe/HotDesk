<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets owners and admins of the route's {organization} through.
 */
class EnsureUserCanManageOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $request->route('organization');

        abort_unless($organization instanceof Organization, Response::HTTP_NOT_FOUND);
        abort_unless($request->user()?->roleIn($organization)?->canManage() === true, Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
