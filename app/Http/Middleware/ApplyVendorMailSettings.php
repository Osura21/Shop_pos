<?php

namespace App\Http\Middleware;

use App\Models\VendorMailSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyVendorMailSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = (int) ($request->user('vendor')?->tenant_id ?? 0);

        if ($tenantId > 0) {
            VendorMailSetting::query()
                ->where('tenant_id', $tenantId)
                ->where('active', true)
                ->first()
                ?->applyToConfig();
        }

        return $next($request);
    }
}
