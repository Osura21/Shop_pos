<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VendorImpersonationController extends Controller
{
    public function accept(Request $request, string $token)
    {
        $cacheKey = "vendor_impersonation:{$token}";
        $payload = Cache::pull($cacheKey);

        if (!$payload || !isset($payload['tenant_id'], $payload['user_id'])) {
            abort(403, 'This impersonation link is invalid or expired.');
        }

        $tenant = tenant();

        if (!$tenant || (int) $tenant->id !== (int) $payload['tenant_id']) {
            abort(403, 'This impersonation link does not match this vendor.');
        }

        $user = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('id', $payload['user_id'])
            ->where('status', 1)
            ->whereHas('roles', fn ($query) => $query->where('guard_name', 'vendor'))
            ->firstOrFail();

        Auth::guard('vendor')->logout();
        Auth::guard('vendor')->login($user);

        $request->session()->regenerate();
        $request->session()->put('impersonated_by_superadmin_id', $payload['superadmin_id'] ?? null);

        $log = ActivityLogger::logLogin($user, $request);
        $request->session()->put('authentication_log_id', $log->id);

        return redirect()->route('vendor.dashboard');
    }
}
