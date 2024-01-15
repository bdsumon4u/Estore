<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncSpatiePermissionsWithFilamentTenants
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        session()->put('tenant_id', $tenant->getKey());
        $spatie = getPermissionsTeamId();

        if ($tenant !== $spatie) {
            setPermissionsTeamId($tenant);
        }

        return $next($request);
    }
}
