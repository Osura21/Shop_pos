<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
// dd($request);
        $request->session()->regenerate();

        $user = $request->user();
        $host = $request->getHost();
        $mainDomain = config('app.main_domain');

        // SUPER ADMIN LOGIN
        if ($host === $mainDomain && $user->tenant_id !== null) {
            auth()->logout();
            abort(403, 'Vendor accounts cannot login on super admin panel');
        }

        // VENDOR LOGIN
        if ($host !== $mainDomain && $user->tenant_id === null) {
            auth()->logout();
            abort(403, 'Super admin cannot login to vendor panel');
        }

        return redirect()->intended(route('superadmin.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
