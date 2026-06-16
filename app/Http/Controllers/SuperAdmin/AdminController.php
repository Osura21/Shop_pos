<?php

namespace App\Http\Controllers\SuperAdmin;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // Update vendor profile
    public function updateProfile(Request $request)
    {
        try {
            $SuperAdmin = Auth::guard('superadmin')->user();

            if (!$SuperAdmin) {
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $SuperAdmin->id,
                'phone' => 'nullable|string|max:255|unique:users,phone,' . $SuperAdmin->id,
            ]);

            $SuperAdmin->update($validated);

            return back()->with('success', 'Profile updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;

        } catch (\Exception $e) {
            Log::error('SuperAdmin profile update failed: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Something went wrong while updating profile.'
            ]);
        }
    }

    // Update vendor password
    public function updatePassword(Request $request)
    {
        try {
            $request->merge([
                'current_password' => $request->currentPassword,
                'password_confirmation' => $request->confirmPassword,
            ]);

            $SuperAdmin = Auth::guard('superadmin')->user();

            if (!$SuperAdmin) {
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', 'confirmed', Password::min(6)],
            ]);

            if (!Hash::check($request->current_password, $SuperAdmin->password)) {
                return back()->withErrors([
                    'current_password' => 'Current password is incorrect'
                ]);
            }

            $SuperAdmin->update([
                'password' => Hash::make($request->password),
            ]);

            return back()->with('success', 'Password updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for password update', [
                'SuperAdmin_id' => optional(Auth::guard('superadmin')->user())->id,
                'errors' => $e->errors()
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('Vendor password update failed', [
                'SuperAdmin_id' => optional(Auth::guard('superadmin')->user())->id,
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => 'Something went wrong while updating password.'
            ]);
        }
    }
}