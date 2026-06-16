<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Tenant;
use App\Models\Theme;
use App\Models\User;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    private const WEBSITE_ORDER_TYPES = [
        'delivery' => 'Delivery',
        'pickup' => 'Pickup',
        'scheduled' => 'Scheduled',
        'table_booking' => 'Table Booking',
    ];

    private function normalizeSriLankaPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '94' . substr($digits, 1);
        }

        if (str_starts_with($digits, '94')) {
            return '+' . $digits;
        }

        return '+' . ltrim($digits, '+');
    }

    public function index()
    {
        return Inertia::render('SuperAdmin/Vendors/Index');
    }

    public function getData(Request $request)
    {
        $search = trim((string) $request->input('search.value', ''));

        $query = Tenant::query()
            ->select(['id', 'name', 'slug', 'theme_id', 'status', 'address', 'city', 'state_province', 'country', 'contact', 'created_at'])
            ->with(['theme:id,name', 'domains:id,tenant_id,domain,is_primary'])
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('address_line_1', 'like', "%{$search}%")
                    ->orWhere('address_line_2', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state_province', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('postal_code', 'like', "%{$search}%")
                    ->orWhere('contact', 'like', "%{$search}%")
                    ->orWhereHas('domains', function ($d) use ($search) {
                        $d->where('domain', 'like', "%{$search}%");
                    });
            });
        }

        return DataTables::of($query)
            ->addColumn('domain', function (Tenant $t) {
                $primary = $t->domains?->firstWhere('is_primary', true);
                return $primary?->domain ?? ($t->domains?->first()?->domain ?? '-');
            })
            ->addColumn('theme', fn (Tenant $t) => $t->theme?->name ?? '-')
            ->addColumn('logo_url', fn (Tenant $t) => $t->getFirstMediaUrl('VendorLogo'))
            ->editColumn('status', fn (Tenant $t) => $t->status ?? 'active')
            ->editColumn('created_at', fn (Tenant $t) => optional($t->created_at)->format('Y-m-d H:i'))
            ->toJson();
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/Vendors/CreateUpdate', [
            'vendor' => null,
            'themes' => Theme::query()
                ->where('is_active', true)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'subscriptionPlans' => $this->subscriptionPlanOptions(),
            'websiteOrderTypeOptions' => $this->websiteOrderTypeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'slug'           => ['required', 'string', 'max:120', 'unique:tenants,slug'],
            'domain'         => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'theme_id'       => ['required', 'exists:themes,id'],
            'status'         => ['nullable', 'in:active,inactive'],
            'legal_business_name' => ['nullable', 'string', 'max:255'],
            'store_display_name' => ['nullable', 'string', 'max:255'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'business_registration_number' => ['nullable', 'string', 'max:255'],
            'food_types' => ['nullable', 'array'],
            'food_types.*' => ['string', 'max:255'],
            'opening_from' => ['nullable', 'date_format:H:i'],
            'opening_to' => ['nullable', 'date_format:H:i'],
            'vendor_subscription_plan_id' => ['required', 'exists:vendor_subscription_plans,id'],
            'vendor_subscription_status' => ['nullable', 'in:inactive,trialing,active,past_due,suspended,cancelled'],
            'vendor_panel_enabled' => ['nullable', 'boolean'],
            'address'        => ['nullable', 'string', 'max:255'],
            'contact'        => ['required', 'string', 'max:50'],
            'country'        => ['nullable', 'string', 'max:120'],
            'country_code'   => ['nullable', 'string', 'size:2'],
            'search_location' => ['nullable', 'string', 'max:255'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'postal_code'    => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:120'],
            'city'           => ['nullable', 'string', 'max:120'],
            'website_order_types' => ['nullable', 'array'],
            'website_order_types.*' => ['string', Rule::in(array_keys(self::WEBSITE_ORDER_TYPES))],
            'logo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'admin_name'     => ['required', 'string', 'max:120'],
            'admin_email'    => ['required', 'email', 'max:190', 'unique:users,email'],
            'admin_phone'    => ['required', 'regex:/^\+94\d{9}$/', 'unique:users,phone'],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        DB::beginTransaction();

        try {
            $normalizedPhone = $this->normalizeSriLankaPhone($data['admin_phone']);

            $tenant = Tenant::create([
                'name'     => $data['name'],
                'slug'     => $data['slug'],
                'theme_id' => $data['theme_id'],
                'status'   => $data['status'] ?? 'active',
                'legal_business_name' => $data['legal_business_name'] ?? null,
                'store_display_name' => $data['store_display_name'] ?? null,
                'vendor_subscription_plan_id' => $data['vendor_subscription_plan_id'] ?? null,
                'vendor_subscription_status' => $data['vendor_subscription_status'] ?? 'active',
                'vendor_panel_enabled' => (bool) ($data['vendor_panel_enabled'] ?? true),
                'vendor_subscription_started_at' => !empty($data['vendor_subscription_plan_id']) ? now() : null,
                'address'  => $this->resolvedAddress($data),
                ...$this->locationPayload($data),
                'contact'  => $data['contact'],
                'business_email' => $data['business_email'] ?? null,
                'owner_name' => $data['owner_name'] ?? null,
                'business_registration_number' => $data['business_registration_number'] ?? null,
                'food_types' => array_values($data['food_types'] ?? []),
                'opening_from' => $data['opening_from'] ?? null,
                'opening_to' => $data['opening_to'] ?? null,
                'website_order_types' => array_values($data['website_order_types'] ?? []),
            ]);

            $tenant->save();

            if ($request->hasFile('logo')) {
                count($tenant->getMedia('VendorLogo')) > 0 ? $tenant->getMedia('VendorLogo')[0]->delete() : null;
                $tenant->addMedia($request->file('logo'))->toMediaCollection('VendorLogo');
                $tenant->save();
            }

            Domain::create([
                'tenant_id'  => $tenant->id,
                'domain'     => $data['domain'],
                'is_primary' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['admin_name'],
                'email'     => $data['admin_email'],
                'phone'     => $normalizedPhone,
                'password'  => Hash::make($data['admin_password']),
                'status'    => 1,
            ]);

            $role = Role::where('guard_name', 'vendor')
                ->whereNull('tenant_id')
                ->firstOrFail();

            $user->assignRole($role);

            DB::commit();

            return redirect()
                ->route('vendors.index')
                ->with('success', "Vendor created: {$tenant->name}");
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function edit(Tenant $vendor)
    {
        $vendor->load(['theme:id,name', 'domains:id,tenant_id,domain,is_primary']);

        $primaryDomain = $vendor->domains?->firstWhere('is_primary', true)?->domain
            ?? $vendor->domains?->first()?->domain
            ?? '';

        $admin = User::query()
            ->where('tenant_id', $vendor->id)
            ->orderBy('id')
            ->first();

        return Inertia::render('SuperAdmin/Vendors/CreateUpdate', [
            'vendor' => [
                'id'          => $vendor->id,
                'name'        => $vendor->name,
                'legal_business_name' => $vendor->legal_business_name ?? '',
                'store_display_name' => $vendor->store_display_name ?? '',
                'slug'        => $vendor->slug,
                'domain'      => $primaryDomain,
                'theme_id'    => $vendor->theme_id,
                'status'      => $vendor->status ?? 'active',
                'address'     => $vendor->address ?? '',
                'country'     => $vendor->country ?? '',
                'country_code' => $vendor->country_code ?? '',
                'search_location' => $vendor->search_location ?? '',
                'google_place_id' => $vendor->google_place_id ?? '',
                'postal_code' => $vendor->postal_code ?? '',
                'address_line_1' => $vendor->address_line_1 ?? '',
                'address_line_2' => $vendor->address_line_2 ?? '',
                'state_province' => $vendor->state_province ?? '',
                'city'        => $vendor->city ?? '',
                'contact'     => $vendor->contact ?? '',
                'business_email' => $vendor->business_email ?? '',
                'owner_name' => $vendor->owner_name ?? '',
                'business_registration_number' => $vendor->business_registration_number ?? '',
                'food_types' => $vendor->food_types ?? [],
                'opening_from' => $vendor->opening_from ?? '',
                'opening_to' => $vendor->opening_to ?? '',
                'website_order_types' => $vendor->website_order_types ?? [],
                'logo_url'    => $vendor->getFirstMediaUrl('VendorLogo'),

                'admin_name'  => $admin?->name ?? '',
                'admin_email' => $admin?->email ?? '',
                'admin_phone' => $admin?->phone ?? '',
                'admin_id'    => $admin?->id,

                'vendor_subscription_plan_id' => $vendor->vendor_subscription_plan_id,
                'vendor_subscription_status' => $vendor->vendor_subscription_status ?? 'active',
                'vendor_panel_enabled' => (bool) $vendor->vendor_panel_enabled,
            ],
            'themes' => Theme::query()
                ->where('is_active', true)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'subscriptionPlans' => $this->subscriptionPlanOptions($vendor->vendor_subscription_plan_id),
            'websiteOrderTypeOptions' => $this->websiteOrderTypeOptions(),
        ]);
    }

    public function show(Tenant $vendor)
    {
        $vendor->load([
            'theme:id,name',
            'domains:id,tenant_id,domain,is_primary',
            'vendorSubscriptionPlan.features',
        ]);

        $admin = User::query()
            ->where('tenant_id', $vendor->id)
            ->whereHas('roles', fn ($q) => $q->where('guard_name', 'vendor'))
            ->orderBy('id')
            ->first();

        return response()->json([
            'vendor' => $this->vendorViewPayload($vendor, $admin),
        ]);
    }

    public function updateSubscription(Request $request, Tenant $vendor)
    {
        $validated = $request->validate([
            'vendor_subscription_plan_id' => ['nullable', 'integer', 'exists:vendor_subscription_plans,id'],
            'vendor_subscription_status' => ['nullable', Rule::in(['inactive', 'trialing', 'active', 'past_due', 'suspended', 'cancelled'])],
        ]);

        $planId = $validated['vendor_subscription_plan_id'] ?? null;

        if (!$planId) {
            $planId = VendorSubscriptionPlan::query()
                ->where('status', 'active')
                ->where('is_default', true)
                ->value('id');
        }

        if (!$planId) {
            return response()->json([
                'message' => 'No default active vendor subscription plan is available.',
            ], 422);
        }

        $vendor->update([
            'vendor_subscription_plan_id' => $planId,
            'vendor_subscription_status' => $validated['vendor_subscription_status'] ?? 'active',
            'vendor_subscription_started_at' => $vendor->vendor_subscription_started_at ?: now(),
        ]);

        $vendor->load(['theme:id,name', 'domains:id,tenant_id,domain,is_primary', 'vendorSubscriptionPlan.features']);

        $admin = User::query()
            ->where('tenant_id', $vendor->id)
            ->whereHas('roles', fn ($q) => $q->where('guard_name', 'vendor'))
            ->orderBy('id')
            ->first();

        return response()->json([
            'message' => 'Vendor subscription updated successfully.',
            'vendor' => $this->vendorViewPayload($vendor, $admin),
        ]);
    }

    public function toggleStatus(Tenant $vendor)
    {
        $vendor->update([
            'status' => ($vendor->status ?? 'active') === 'active' ? 'inactive' : 'active',
        ]);

        $vendor->load(['theme:id,name', 'domains:id,tenant_id,domain,is_primary', 'vendorSubscriptionPlan.features']);

        $admin = User::query()
            ->where('tenant_id', $vendor->id)
            ->whereHas('roles', fn ($q) => $q->where('guard_name', 'vendor'))
            ->orderBy('id')
            ->first();

        return response()->json([
            'message' => 'Vendor status updated successfully.',
            'vendor' => $this->vendorViewPayload($vendor, $admin),
        ]);
    }

    public function updateProfileDetails(Request $request, Tenant $vendor)
    {
        $admin = User::query()
            ->where('tenant_id', $vendor->id)
            ->whereHas('roles', fn ($q) => $q->where('guard_name', 'vendor'))
            ->orderBy('id')
            ->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'legal_business_name' => ['nullable', 'string', 'max:255'],
            'store_display_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'contact' => ['nullable', 'string', 'max:50'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'business_registration_number' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'search_location' => ['nullable', 'string', 'max:255'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'food_types' => ['nullable', 'array'],
            'food_types.*' => ['string', 'max:255'],
            'website_order_types' => ['nullable', 'array'],
            'website_order_types.*' => ['string', 'max:255'],
            'opening_from' => ['nullable', 'date_format:H:i'],
            'opening_to' => ['nullable', 'date_format:H:i'],
            'admin_name' => ['nullable', 'string', 'max:120'],
            'admin_email' => ['nullable', 'email', 'max:190', $admin ? "unique:users,email,{$admin->id}" : 'unique:users,email'],
            'admin_phone' => ['nullable', 'string', 'max:50', $admin ? "unique:users,phone,{$admin->id}" : 'unique:users,phone'],
        ]);

        $vendor->update([
            'name' => $validated['name'],
            'legal_business_name' => $validated['legal_business_name'] ?? null,
            'store_display_name' => $validated['store_display_name'] ?? null,
            'address' => $validated['address'] ?? null,
            'contact' => $validated['contact'] ?? null,
            'business_email' => $validated['business_email'] ?? null,
            'owner_name' => $validated['owner_name'] ?? null,
            'business_registration_number' => $validated['business_registration_number'] ?? null,
            'country' => $this->cleanCountryName($validated['country'] ?? null),
            'country_code' => isset($validated['country_code']) ? strtoupper($validated['country_code']) : null,
            'search_location' => $validated['search_location'] ?? null,
            'google_place_id' => $validated['google_place_id'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'state_province' => $validated['state_province'] ?? null,
            'city' => $validated['city'] ?? null,
            'food_types' => array_values($validated['food_types'] ?? []),
            'website_order_types' => $this->websiteOrderTypesFromInput($validated['website_order_types'] ?? []),
            'opening_from' => $validated['opening_from'] ?? null,
            'opening_to' => $validated['opening_to'] ?? null,
        ]);

        if ($admin) {
            $admin->name = $validated['admin_name'] ?? $admin->name;
            $admin->email = $validated['admin_email'] ?? $admin->email;
            $admin->phone = $validated['admin_phone'] ?? $admin->phone;
            $admin->save();
        }

        $vendor->load(['theme:id,name', 'domains:id,tenant_id,domain,is_primary', 'vendorSubscriptionPlan.features']);

        $admin = User::query()
            ->where('tenant_id', $vendor->id)
            ->whereHas('roles', fn ($q) => $q->where('guard_name', 'vendor'))
            ->orderBy('id')
            ->first();

        return response()->json([
            'message' => 'Vendor profile details updated successfully.',
            'vendor' => $this->vendorViewPayload($vendor, $admin),
        ]);
    }

    public function impersonate(Request $request, Tenant $vendor)
    {
        $vendor->load('domains:id,tenant_id,domain,is_primary');

        $domain = $vendor->domains?->firstWhere('is_primary', true)?->domain
            ?? $vendor->domains?->first()?->domain;

        if (!$domain) {
            return back()->with('error', 'This vendor does not have a domain to open.');
        }

        $admin = User::query()
            ->where('tenant_id', $vendor->id)
            ->where('status', 1)
            ->whereHas('roles', fn ($query) => $query->where('guard_name', 'vendor'))
            ->orderBy('id')
            ->first();

        if (!$admin) {
            return back()->with('error', 'This vendor does not have an active admin user.');
        }

        $token = (string) str()->random(64);

        Cache::put("vendor_impersonation:{$token}", [
            'tenant_id' => $vendor->id,
            'user_id' => $admin->id,
            'superadmin_id' => Auth::guard('superadmin')->id(),
        ], now()->addMinutes(2));

        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: $request->getScheme();

        return redirect()->away("{$scheme}://{$domain}/vendor-admin/impersonate/{$token}");
    }
    public function update(Request $request, Tenant $vendor)
    {
        $vendor->load('domains:id,tenant_id,domain,is_primary');

        $primaryDomainRow = $vendor->domains?->firstWhere('is_primary', true);

        $admin = User::query()
            ->where('tenant_id', $vendor->id)
            ->whereHas('roles', fn ($q) => $q->where('guard_name', 'vendor'))
            ->orderBy('id')
            ->first();

        $adminId = $admin?->id;

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'slug'           => ['required', 'string', 'max:120', "unique:tenants,slug,{$vendor->id}"],
            'domain'         => ['required', 'string', 'max:255', $primaryDomainRow
                                ? "unique:domains,domain,{$primaryDomainRow->id}"
                                : 'unique:domains,domain'],
            'theme_id'       => ['required', 'exists:themes,id'],
            'status'         => ['required', 'in:active,inactive'],
            'legal_business_name' => ['nullable', 'string', 'max:255'],
            'store_display_name' => ['nullable', 'string', 'max:255'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'business_registration_number' => ['nullable', 'string', 'max:255'],
            'food_types' => ['nullable', 'array'],
            'food_types.*' => ['string', 'max:255'],
            'opening_from' => ['nullable', 'date_format:H:i'],
            'opening_to' => ['nullable', 'date_format:H:i'],
            'vendor_subscription_plan_id' => ['required', 'exists:vendor_subscription_plans,id'],
            'vendor_subscription_status' => ['nullable', 'in:inactive,trialing,active,past_due,suspended,cancelled'],
            'vendor_panel_enabled' => ['nullable', 'boolean'],
            'address'        => ['nullable', 'string', 'max:255'],
            'contact'        => ['required', 'string', 'max:50'],
            'country'        => ['nullable', 'string', 'max:120'],
            'country_code'   => ['nullable', 'string', 'size:2'],
            'search_location' => ['nullable', 'string', 'max:255'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'postal_code'    => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:120'],
            'city'           => ['nullable', 'string', 'max:120'],
            'website_order_types' => ['nullable', 'array'],
            'website_order_types.*' => ['string', Rule::in(array_keys(self::WEBSITE_ORDER_TYPES))],
            'logo'           => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'admin_name'     => ['required', 'string', 'max:120'],
            'admin_email'    => ['required', 'email', 'max:190', $adminId ? "unique:users,email,{$adminId}" : 'unique:users,email'],
            'admin_phone'    => ['required', 'regex:/^\+94\d{9}$/', $adminId ? "unique:users,phone,{$adminId}" : 'unique:users,phone'],
            'admin_password' => ['nullable', 'string', 'min:8'],
        ]);

        DB::beginTransaction();

        try {
            $normalizedPhone = $this->normalizeSriLankaPhone($data['admin_phone']);

            $vendor->update([
                'name'     => $data['name'],
                'slug'     => $data['slug'],
                'theme_id' => $data['theme_id'],
                'status'   => $data['status'],
                'legal_business_name' => $data['legal_business_name'] ?? null,
                'store_display_name' => $data['store_display_name'] ?? null,
                'vendor_subscription_plan_id' => $data['vendor_subscription_plan_id'] ?? null,
                'vendor_subscription_status' => $data['vendor_subscription_status'] ?? 'active',
                'vendor_panel_enabled' => (bool) ($data['vendor_panel_enabled'] ?? true),
                'vendor_subscription_started_at' => !empty($data['vendor_subscription_plan_id'])
                    ? ($vendor->vendor_subscription_started_at ?: now())
                    : null,
                'address'  => $this->resolvedAddress($data),
                ...$this->locationPayload($data),
                'contact'  => $data['contact'],
                'business_email' => $data['business_email'] ?? null,
                'owner_name' => $data['owner_name'] ?? null,
                'business_registration_number' => $data['business_registration_number'] ?? null,
                'food_types' => array_values($data['food_types'] ?? []),
                'opening_from' => $data['opening_from'] ?? null,
                'opening_to' => $data['opening_to'] ?? null,
                'website_order_types' => array_values($data['website_order_types'] ?? []),
            ]);

            $vendor->save();

            if ($request->hasFile('logo')) {
                count($vendor->getMedia('VendorLogo')) > 0 ? $vendor->getMedia('VendorLogo')[0]->delete() : null;
                $vendor->addMedia($request->file('logo'))->toMediaCollection('VendorLogo');
                $vendor->save();
            }

            if ($primaryDomainRow) {
                $primaryDomainRow->update([
                    'domain' => $data['domain'],
                ]);
            } else {
                Domain::create([
                    'tenant_id'  => $vendor->id,
                    'domain'     => $data['domain'],
                    'is_primary' => true,
                ]);
            }

            if ($admin) {
                $admin->name = $data['admin_name'];
                $admin->email = $data['admin_email'];
                $admin->phone = $normalizedPhone;

                if (!empty($data['admin_password'])) {
                    $admin->password = Hash::make($data['admin_password']);
                }

                $admin->save();
            } else {
                $newAdmin = User::create([
                    'tenant_id' => $vendor->id,
                    'name'      => $data['admin_name'],
                    'email'     => $data['admin_email'],
                    'phone'     => $normalizedPhone,
                    'password'  => Hash::make($data['admin_password'] ?? str()->random(12)),
                    'status'    => 1,
                ]);

                $role = Role::where('guard_name', 'vendor')
                    ->whereNull('tenant_id')
                    ->firstOrFail();

                $newAdmin->assignRole($role);
            }

            DB::commit();

            return redirect()
                ->route('vendors.index')
                ->with('success', 'Vendor updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(Tenant $vendor)
    {
        if ($vendor->getFirstMedia('VendorLogo')) {
            $vendor->getFirstMedia('VendorLogo')->delete();
        }

        $vendor->delete();

        return redirect()
            ->route('vendors.index')
            ->with('success', 'Vendor deleted successfully.');
    }

    public function locations(Request $request)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2'],
        ]);

        $apiKey = config('services.google.maps_api_key');

        if (! $apiKey) {
            return response()->json(['predictions' => []]);
        }

        $response = $this->googlePlacesAutocomplete($data['query'], $data['country_code'] ?? null, $apiKey);

        if (! $response->ok()) {
            return response()->json(['predictions' => []]);
        }

        return response()->json([
            'predictions' => collect($response->json('predictions', []))
                ->map(fn (array $item) => [
                    'place_id' => $item['place_id'] ?? '',
                    'description' => $item['description'] ?? '',
                    'main_text' => data_get($item, 'structured_formatting.main_text', $item['description'] ?? ''),
                    'secondary_text' => data_get($item, 'structured_formatting.secondary_text', ''),
                ])
                ->filter(fn (array $item) => $item['place_id'] !== '' && $item['description'] !== '')
                ->values(),
        ]);
    }

    public function locationDetails(Request $request)
    {
        $data = $request->validate([
            'place_id' => ['required', 'string', 'max:255'],
        ]);

        $apiKey = config('services.google.maps_api_key');

        if (! $apiKey) {
            return response()->json(['location' => null]);
        }

        $response = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $data['place_id'],
            'fields' => 'place_id,formatted_address,address_components',
            'key' => $apiKey,
        ]);

        if (! $response->ok() || $response->json('status') !== 'OK') {
            return response()->json(['location' => null]);
        }

        $result = $response->json('result', []);

        return response()->json([
            'location' => $this->googleLocationPayload($result),
        ]);
    }

    private function googlePlacesAutocomplete(string $query, ?string $countryCode, string $apiKey)
    {
        $autocompleteUrl = (string) config('services.google.maps_autocomplete_url');

        if (str_contains($autocompleteUrl, '${query}')) {
            $url = str_replace(
                ['${query}', '${GOOGLE_MAPS_API_KEY}'],
                [rawurlencode($query), $apiKey],
                $autocompleteUrl
            );

            if ($countryCode) {
                $url = preg_replace(
                    '/components=country:[a-z]{2}/i',
                    'components=country:' . strtoupper($countryCode),
                    $url
                );
            }

            return Http::timeout(6)->get($url);
        }

        return Http::timeout(6)->get($autocompleteUrl, [
            'input' => $query,
            'components' => $countryCode ? 'country:' . strtolower($countryCode) : null,
            'key' => $apiKey,
        ]);
    }

    private function googleLocationPayload(array $result): array
    {
        $component = function (string $type, string $key = 'long_name') use ($result): string {
            foreach ($result['address_components'] ?? [] as $component) {
                if (in_array($type, $component['types'] ?? [], true)) {
                    return (string) ($component[$key] ?? '');
                }
            }

            return '';
        };

        $streetNumber = $component('street_number');
        $route = $component('route');
        $addressLine1 = trim(collect([$streetNumber, $route])->filter()->join(' '));

        if ($addressLine1 === '') {
            $addressLine1 = $component('premise') ?: $component('point_of_interest') ?: $component('establishment');
        }

        $city = $component('locality')
            ?: $component('postal_town')
            ?: $component('administrative_area_level_2')
            ?: $component('sublocality');

        return [
            'google_place_id' => $result['place_id'] ?? '',
            'search_location' => $result['formatted_address'] ?? '',
            'address' => $result['formatted_address'] ?? '',
            'address_line_1' => $addressLine1,
            'address_line_2' => $component('sublocality') ?: $component('neighborhood'),
            'city' => $city,
            'state_province' => $component('administrative_area_level_1'),
            'postal_code' => $component('postal_code'),
            'country' => $this->cleanCountryName($component('country')),
            'country_code' => strtoupper($component('country', 'short_name')),
        ];
    }

    private function locationPayload(array $data): array
    {
        return [
            'country' => $this->cleanCountryName($data['country'] ?? null),
            'country_code' => isset($data['country_code']) ? strtoupper($data['country_code']) : null,
            'search_location' => $data['search_location'] ?? null,
            'google_place_id' => $data['google_place_id'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'address_line_1' => $data['address_line_1'] ?? null,
            'address_line_2' => $data['address_line_2'] ?? null,
            'state_province' => $data['state_province'] ?? null,
            'city' => $data['city'] ?? null,
        ];
    }

    private function resolvedAddress(array $data): ?string
    {
        $address = trim((string) ($data['address'] ?? ''));

        if ($address !== '') {
            return $address;
        }

        $searchLocation = trim((string) ($data['search_location'] ?? ''));

        if ($searchLocation !== '') {
            return $searchLocation;
        }

        $parts = collect([
            $data['address_line_1'] ?? null,
            $data['address_line_2'] ?? null,
            $data['city'] ?? null,
            $data['state_province'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
        ])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        return $parts->isNotEmpty() ? $parts->join(', ') : null;
    }

    private function cleanCountryName(?string $country): ?string
    {
        $country = trim((string) $country);

        if ($country === '') {
            return null;
        }

        return trim(preg_replace('/\s*\([^)]*\)\s*/', '', $country));
    }

    private function subscriptionPlanOptions(?int $selectedPlanId = null)
    {
        return VendorSubscriptionPlan::query()
            ->where(function ($query) use ($selectedPlanId) {
                $query->where('status', 'active');

                if ($selectedPlanId) {
                    $query->orWhere('id', $selectedPlanId);
                }
            })
            ->select(['id', 'plan_name', 'plan_code', 'monthly_price', 'yearly_price', 'currency_code', 'auto_renew', 'is_default'])
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('plan_name')
            ->get()
            ->map(fn (VendorSubscriptionPlan $plan) => [
                'id' => $plan->id,
                'name' => "{$plan->plan_name} ({$plan->plan_code})",
                'plan_name' => $plan->plan_name,
                'plan_code' => $plan->plan_code,
                'monthly_price' => $plan->monthly_price,
                'yearly_price' => $plan->yearly_price,
                'currency_code' => $plan->currency_code,
                'auto_renew' => (bool) $plan->auto_renew,
                'is_default' => (bool) $plan->is_default,
            ]);
    }

    private function websiteOrderTypeOptions(): array
    {
        return collect(self::WEBSITE_ORDER_TYPES)
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function websiteOrderTypesFromInput(array $values): array
    {
        return collect($values)
            ->map(fn ($value) => Str::of($value)->lower()->replace([' ', '-'], '_')->toString())
            ->map(fn ($value) => match ($value) {
                'delivery' => 'delivery',
                'pickup', 'pick_up' => 'pickup',
                'scheduled', 'scheduled_orders', 'sheduled' => 'scheduled',
                'table_booking', 'table_bookings' => 'table_booking',
                default => null,
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function vendorViewPayload(Tenant $vendor, ?User $admin = null): array
    {
        $primaryDomain = $vendor->domains?->firstWhere('is_primary', true);
        $plan = $vendor->vendorSubscriptionPlan;

        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'slug' => $vendor->slug,
            'status' => $vendor->status ?? 'active',
            'source_requested_vendor_id' => $vendor->source_requested_vendor_id,
            'legal_business_name' => $vendor->legal_business_name,
            'store_display_name' => $vendor->store_display_name,
            'address' => $vendor->address,
            'country' => $vendor->country,
            'country_code' => $vendor->country_code,
            'search_location' => $vendor->search_location,
            'google_place_id' => $vendor->google_place_id,
            'postal_code' => $vendor->postal_code,
            'address_line_1' => $vendor->address_line_1,
            'address_line_2' => $vendor->address_line_2,
            'state_province' => $vendor->state_province,
            'city' => $vendor->city,
            'contact' => $vendor->contact,
            'business_email' => $vendor->business_email,
            'owner_name' => $vendor->owner_name,
            'business_registration_number' => $vendor->business_registration_number,
            'food_types' => $vendor->food_types ?? [],
            'opening_from' => $vendor->opening_from,
            'opening_to' => $vendor->opening_to,
            'website_order_types' => $vendor->website_order_types ?? [],
            'logo_url' => $vendor->getFirstMediaUrl('VendorLogo'),
            'business_logo_url' => $vendor->business_logo_path ? Storage::disk('public')->url($vendor->business_logo_path) : null,
            'business_photo_urls' => collect($vendor->business_photo_paths ?? [])
                ->map(fn ($path) => Storage::disk('public')->url($path))
                ->values()
                ->all(),
            'restaurant_page_image_urls' => collect($vendor->restaurant_page_image_paths ?? [])
                ->mapWithKeys(fn ($path, $key) => [
                    $key => Str::startsWith($path, ['http://', 'https://'])
                        ? $path
                        : (Str::startsWith($path, ['vendor-requests/'])
                            ? Storage::disk('public')->url($path)
                            : asset(ltrim($path, '/'))),
                ])
                ->all(),
            'business_license_url' => $vendor->business_license_path ? Storage::disk('public')->url($vendor->business_license_path) : null,
            'primary_domain' => $primaryDomain?->domain ?? ($vendor->domains?->first()?->domain ?? null),
            'domains' => $vendor->domains?->map(fn (Domain $domain) => [
                'id' => $domain->id,
                'domain' => $domain->domain,
                'is_primary' => (bool) $domain->is_primary,
            ])->values()->all() ?? [],
            'theme' => $vendor->theme?->name,
            'admin' => [
                'id' => $admin?->id,
                'name' => $admin?->name,
                'email' => $admin?->email,
                'phone' => $admin?->phone,
                'status' => $admin ? ((int) $admin->status === 1 ? 'active' : 'inactive') : null,
            ],
            'subscription' => [
                'plan_id' => $vendor->vendor_subscription_plan_id,
                'status' => $vendor->vendor_subscription_status ?? 'active',
                'panel_enabled' => (bool) $vendor->vendor_panel_enabled,
                'started_at' => optional($vendor->vendor_subscription_started_at)->format('M d, Y h:i A'),
                'ends_at' => optional($vendor->vendor_subscription_ends_at)->format('M d, Y h:i A'),
                'trial_ends_at' => optional($vendor->vendor_trial_ends_at)->format('M d, Y h:i A'),
                'plan' => $plan ? $this->subscriptionPlanPayload($plan) : null,
            ],
            'counts' => [
                'domains' => $vendor->domains()->count(),
                'branches' => $vendor->branches()->count(),
                'products' => $vendor->products()->count(),
                'categories' => $vendor->categories()->count(),
                'users' => $vendor->users()->count(),
            ],
            'created_at' => optional($vendor->created_at)->format('M d, Y h:i A'),
            'updated_at' => optional($vendor->updated_at)->format('M d, Y h:i A'),
            'available_subscription_plans' => $this->subscriptionPlanOptions($vendor->vendor_subscription_plan_id)
                ->map(fn ($plan) => [
                    ...$plan,
                    'label' => "{$plan['plan_name']} - {$plan['currency_code']} " . number_format((float) $plan['monthly_price'], 2) . " / month" . ($plan['is_default'] ? ' (Default)' : ''),
                ])
                ->values()
                ->all(),
        ];
    }

    private function subscriptionPlanPayload(VendorSubscriptionPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'plan_name' => $plan->plan_name,
            'plan_code' => $plan->plan_code,
            'short_description' => $plan->short_description,
            'status' => $plan->status,
            'is_default' => (bool) $plan->is_default,
            'monthly_price' => (float) $plan->monthly_price,
            'yearly_price' => (float) $plan->yearly_price,
            'currency_code' => $plan->currency_code,
            'trial_days' => (int) $plan->trial_days,
            'auto_renew' => (bool) $plan->auto_renew,
            'features' => $plan->features?->map(fn ($feature) => [
                'name' => $feature->feature_name,
                'key' => $feature->feature_key,
                'enabled' => (bool) $feature->enabled,
                'limit' => $feature->limit_value,
            ])->values()->all() ?? [],
        ];
    }
}
