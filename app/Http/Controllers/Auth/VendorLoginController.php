<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VendorLoginController extends Controller
{
    public function show()
    {
        return Inertia::render('VendorAdmin/Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $tenantId = tenant()?->id;

        if (
            $tenantId &&
            Auth::guard('vendor')->attempt($credentials) &&
            (int) Auth::guard('vendor')->user()->tenant_id === (int) $tenantId
        ) {
            $tenant = tenant();

            if (
                ! (bool) ($tenant->vendor_panel_enabled ?? true) ||
                ! in_array($tenant->vendor_subscription_status ?? 'active', ['active', 'trialing'], true)
            ) {
                Auth::guard('vendor')->logout();

                return back()->withErrors([
                    'email' => 'Vendor panel access is disabled for this subscription. Please contact support.',
                ]);
            }

            $request->session()->regenerate();
            $log = ActivityLogger::logLogin(Auth::guard('vendor')->user(), $request);
            $request->session()->put('authentication_log_id', $log->id);

            return redirect()->route('vendor.dashboard');
        }

        Auth::guard('vendor')->logout();

        return back()->withErrors([
            'email' => 'Invalid vendor credentials',
        ]);
    }

    public function logout(Request $request)
    {
        if ($user = Auth::guard('vendor')->user()) {
            ActivityLogger::logLogout($user, $request);
        }

        Auth::guard('vendor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login');
    }
}
