<?php

namespace App\Http\Middleware;

use App\Services\TenantResolver;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        $tenant = app(TenantResolver::class)->resolveByHost($host);

        if (!$tenant) {
            abort(404, 'Tenant not found for this domain.');
        }

        app(TenantContext::class)->set($tenant);

        return $next($request);
    }
}
