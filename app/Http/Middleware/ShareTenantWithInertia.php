<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShareTenantWithInertia
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = tenant();

        Inertia::share([
            'tenant' => $tenant
                ? array_merge($tenant->toArray(), [
                    'logo_url' => $tenant->getFirstMediaUrl('VendorLogo'),
                    'favicon_url' => $tenant->getFirstMediaUrl('VendorFavicon'),
                ])
                : null,

            'appTitle' => $this->title($request, $tenant),
        ]);

        return $next($request);
    }

    protected function title(Request $request, $tenant): string
    {
        // Vendor domain
        if ($tenant) {
            return $tenant->name . ($request->is('admin*') ? ' - Admin' : '');
        }

        // Central domain
        if ($request->is('admin*')) {
            return config('app.name') . ' - Super Admin';
        }

        return config('app.name');
    }
}
