<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SuperAdminLoginController extends Controller
{
    public function show()
    {
        // dd('test');
        return Inertia::render('SuperAdmin/Auth/Login');
    }

    public function login(Request $request)
    {
        // dd($request);
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (
            Auth::guard('superadmin')->attempt($credentials) &&
            Auth::guard('superadmin')->user()->tenant_id === null
        ) {
            $request->session()->regenerate();
            return redirect()->route('superadmin.dashboard');
        }

        Auth::guard('superadmin')->logout();

        return back()->withErrors([
            'email' => 'Invalid super admin credentials',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

          return redirect()->route('superadmin.login');
    }
}
