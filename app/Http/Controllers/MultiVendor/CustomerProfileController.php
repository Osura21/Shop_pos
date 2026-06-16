<?php

namespace App\Http\Controllers\MultiVendor;

use App\Models\City;
use App\Models\District;
use App\Models\Vehicle;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\SubscriptionPlan;

class CustomerProfileController
{
  public function index(Request $request)
{
    $customer = Auth::guard('customer')->user();

    if (! $customer) {
        return redirect()->route('multivendor.home');
    }

    $vehicles = Vehicle::query()
        ->where('customer_id', $customer->id)
        ->whereNull('deleted_at') 
        ->with([
            'manufacture:id,title',
            'vehicleModel:id,title',
        ])
        ->latest()
        ->paginate(10)
        ->through(fn ($v) => [
            'id'         => $v->id,
            'image_url'  => $v->image_url,
            'price'      => $v->price,
            'year'       => $v->year,
            'mileage'    => $v->mileage,
            'brand'      => $v->manufacture?->title,
            'model'      => $v->vehicleModel?->title,
            'created_at' => optional($v->created_at)->toDateTimeString(),
            'status'     => $v->status,
        ]);

    $customer->load([
    'defaultPlan.planFeatures',
    'subscribedPlan.planFeatures',
]);

$defaultMembershipPlan = SubscriptionPlan::query()
    ->with('planFeatures')
    ->where('is_default', 1)
    ->where('status', 'active')
    ->first();

$effectiveMembershipPlan = null;

if ($customer?->subscribedPlan && $customer->subscribedPlan->status === 'active') {
    $effectiveMembershipPlan = $customer->subscribedPlan;
} elseif ($customer?->defaultPlan && $customer->defaultPlan->status === 'active') {
    $effectiveMembershipPlan = $customer->defaultPlan;
} else {
    $effectiveMembershipPlan = $defaultMembershipPlan;
}
    return Inertia::render('MultiVendor/Customer/Account', [
        'customer'   => $customer,
        'districts'  => District::select('id', 'name')->orderBy('name')->get(),
        'citiesAll'  => City::select('id', 'name', 'district_id')->orderBy('name')->get(),
        'vehicles'   => $vehicles,
       'membershipPlan' => $effectiveMembershipPlan ? [
    'id' => $effectiveMembershipPlan->id,
    'subscription_name' => $effectiveMembershipPlan->subscription_name,
    'subscription_plan_code' => $effectiveMembershipPlan->subscription_plan_code,
    'short_description' => $effectiveMembershipPlan->short_description,
    'price' => $effectiveMembershipPlan->price,
    'billing_interval' => $effectiveMembershipPlan->billing_interval,
    'badge' => $effectiveMembershipPlan->badge,
    'auto_renew' => (int) $effectiveMembershipPlan->auto_renew,
    'cancellation_policy' => $effectiveMembershipPlan->cancellation_policy,
    'refund_policy' => $effectiveMembershipPlan->refund_policy,
    'plan_card_color' => $effectiveMembershipPlan->plan_card_color,
    'plan_icon_url' => $effectiveMembershipPlan->plan_icon ? asset('storage/' . $effectiveMembershipPlan->plan_icon) : null,
    'features' => $effectiveMembershipPlan->planFeatures->map(fn ($feature) => [
        'feature_name' => $feature->feature_name,
        'feature_key' => $feature->feature_key,
        'limit_type' => $feature->limit_type,
        'limit_value' => $feature->limit_value,
    ])->values(),
] : null,
    ]);
}
    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['nullable', 'email', 'max:255', 'unique:customers,email,' . $customer->id],
            'gender'      => ['nullable', 'in:male,female,other'],
            'dob'         => ['nullable', 'date'],
            'c_job'       => ['nullable', 'string', 'max:255'],

            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'city_id'     => ['nullable', 'integer', 'exists:cities,id'],
        ]);

        // ensure city belongs to district (important)
        if (!empty($validated['city_id'])) {
            $city = City::select('id', 'district_id', 'name')->find($validated['city_id']);
            if (!empty($validated['district_id']) && (int)$city->district_id !== (int)$validated['district_id']) {
                return back()->withErrors([
                    'city_id' => 'Selected city does not belong to the selected district.',
                ]);
            }
        }

        // (optional) store readable location string too
        $locationText = null;
        if (!empty($validated['city_id'])) {
            $city = City::select('id','name')->find($validated['city_id']);
            $locationText = $city?->name;
        } elseif (!empty($validated['district_id'])) {
            $district = District::select('id','name')->find($validated['district_id']);
            $locationText = $district?->name;
        }
        $validated['location'] = $locationText;

        $customer->update($validated);

        return back()->with('success', 'Profile updated.');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('multivendor.home');
    }
}
