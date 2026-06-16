<?php

namespace App\Http\Controllers\Vendor;

use Auth;
use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function profile()
    {
        $vendor = Auth::guard('vendor')->user();

        abort_unless($vendor, 403);

        $vendor->loadMissing(['roles', 'branch']);

        $tenant = Tenant::query()
            ->with(['theme:id,name', 'domains:id,tenant_id,domain,is_primary', 'vendorSubscriptionPlan.features'])
            ->find($vendor->tenant_id);

        $plan = $tenant?->vendorSubscriptionPlan;
        $primaryDomain = $tenant?->domains?->firstWhere('is_primary', true)?->domain
            ?? $tenant?->domains?->first()?->domain;

        return Inertia::render('VendorAdmin/Profile/Index', [
            'account' => [
                'name' => $vendor->name,
                'username' => $vendor->username,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'gender' => $vendor->gender,
                'status' => (bool) $vendor->status,
                'role' => $vendor->roles->first()?->name,
                'branch' => $vendor->branch?->name,
                'created_at' => optional($vendor->created_at)?->format('M d, Y h:i A'),
                'updated_at' => optional($vendor->updated_at)?->format('M d, Y h:i A'),
            ],
            'vendor' => [
                'name' => $tenant?->name,
                'brand_name' => $tenant?->brand_name,
                'slug' => $tenant?->slug,
                'primary_domain' => $primaryDomain,
                'theme' => $tenant?->theme?->name,
                'status' => $tenant?->status,
                'address' => $tenant?->address,
                'contact' => $tenant?->contact,
                'panel_enabled' => (bool) ($tenant?->vendor_panel_enabled ?? false),
                'logo_url' => $tenant?->getFirstMediaUrl('VendorLogo') ?: null,
            ],
            'membership' => [
                'status' => $tenant?->vendor_subscription_status ?? 'inactive',
                'started_at' => optional($tenant?->vendor_subscription_started_at)?->format('M d, Y h:i A'),
                'ends_at' => optional($tenant?->vendor_subscription_ends_at)?->format('M d, Y h:i A'),
                'trial_ends_at' => optional($tenant?->vendor_trial_ends_at)?->format('M d, Y h:i A'),
                'plan' => $plan ? [
                    'name' => $plan->plan_name,
                    'code' => $plan->plan_code,
                    'description' => $plan->short_description,
                    'badge' => $plan->badge,
                    'monthly_price' => number_format((float) $plan->monthly_price, 2),
                    'yearly_price' => $plan->yearly_price === null ? null : number_format((float) $plan->yearly_price, 2),
                    'currency_code' => $plan->currency_code,
                    'trial_days' => $plan->trial_days,
                    'auto_renew' => (bool) $plan->auto_renew,
                    'features' => $plan->features->map(fn ($feature) => [
                        'name' => $feature->feature_name,
                        'group' => $feature->feature_group,
                        'enabled' => (bool) $feature->enabled,
                        'is_unlimited' => (bool) $feature->is_unlimited,
                        'limit_value' => $feature->limit_value === null ? null : (float) $feature->limit_value,
                        'unit' => $feature->unit,
                        'notes' => $feature->notes,
                    ])->values(),
                ] : null,
            ],
        ]);
    }

    // Update vendor profile
    public function updateProfile(Request $request)
    {
        try {
            $vendor = Auth::guard('vendor')->user();

            if (!$vendor) {
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $vendor->id,
                'phone' => 'nullable|string|max:255|unique:users,phone,' . $vendor->id,
            ]);

            $vendor->update($validated);

            return back()->with('success', 'Profile updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;

        } catch (\Exception $e) {
            Log::error('Vendor profile update failed: ' . $e->getMessage());

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

            $vendor = Auth::guard('vendor')->user();

            if (!$vendor) {
                return back()->withErrors(['error' => 'Unauthorized access']);
            }

            $request->validate([
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', 'confirmed', Password::min(6)],
            ]);

            if (!Hash::check($request->current_password, $vendor->password)) {
                return back()->withErrors([
                    'current_password' => 'Current password is incorrect'
                ]);
            }

            $vendor->update([
                'password' => Hash::make($request->password),
            ]);

            return back()->with('success', 'Password updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed for password update', [
                'vendor_id' => optional(Auth::guard('vendor')->user())->id,
                'errors' => $e->errors()
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('Vendor password update failed', [
                'vendor_id' => optional(Auth::guard('vendor')->user())->id,
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'error' => 'Something went wrong while updating password.'
            ]);
        }
    }
}
