<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Mail\VendorRequestStatusMail;
use App\Models\Domain;
use App\Models\RequestedVendor;
use App\Models\Tenant;
use App\Models\Theme;
use App\Models\User;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RequestVendorController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/RequestedVendors/Index', [
            'themes' => Theme::query()
                ->where('is_active', true)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'subscriptionPlans' => $this->subscriptionPlanOptions(),
        ]);
    }

    public function getData(Request $request)
    {
        $search = trim((string) $request->input('search.value', ''));

        $query = RequestedVendor::query()
            ->select([
                'id',
                'name',
                'admin_name',
                'email',
                'phone',
                'legal_business_name',
                'store_display_name',
                'business_address',
                'business_email',
                'owner_name',
                'business_registration_number',
                'city',
                'country',
                'country_code',
                'search_location',
                'google_place_id',
                'postal_code',
                'address_line_1',
                'address_line_2',
                'state_province',
                'food_types',
                'opening_from',
                'opening_to',
                'service_options',
                'terms_accepted',
                'business_logo_path',
                'business_photo_paths',
                'restaurant_page_image_paths',
                'business_license_path',
                'status',
                'slug',
                'reason',
                'created_at',
                'updated_at',
            ])
            ->whereIn('status', ['pending', 'rejected']) // approved should be hidden from list
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('admin_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->editColumn('created_at', fn (RequestedVendor $vendor) => optional($vendor->created_at)->format('Y-m-d H:i'))
            ->toJson();
    }

    public function show(RequestedVendor $requestedVendor)
    {
        return response()->json($this->transformVendor($requestedVendor));
    }

    public function approve(Request $request, RequestedVendor $requestedVendor)
    {
        if ($requestedVendor->status === 'approved') {
            return response()->json([
                'message' => 'This vendor request is already approved.',
                'errors' => [
                    'status' => ['This vendor request is already approved.'],
                ],
            ], 422);
        }

        $validated = $request->validate([
            'theme_id' => ['required', 'exists:themes,id'],
            'domain_name' => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'admin_password' => ['required', 'string', 'min:8', 'max:255'],
            'vendor_subscription_plan_id' => ['nullable', 'integer', 'exists:vendor_subscription_plans,id'],
            'vendor_subscription_status' => ['nullable', Rule::in(['inactive', 'trialing', 'active', 'past_due', 'suspended', 'cancelled'])],
            'vendor_panel_enabled' => ['nullable', 'boolean'],
        ]);

        $normalizedEmail = strtolower(trim((string) $requestedVendor->email));
        $normalizedPhone = $this->normalizePhone((string) $requestedVendor->phone);
        $normalizedDomain = strtolower(trim((string) $validated['domain_name']));

        if (User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists()) {
            return response()->json([
                'message' => 'This email is already used by an existing vendor user.',
                'errors' => [
                    'email' => ['This email is already used by an existing vendor user.'],
                ],
            ], 422);
        }

        if (User::where('phone', $normalizedPhone)->exists()) {
            return response()->json([
                'message' => 'This phone number is already used by an existing vendor user.',
                'errors' => [
                    'phone' => ['This phone number is already used by an existing vendor user.'],
                ],
            ], 422);
        }

        $theme = Theme::query()->select(['id', 'name'])->findOrFail($validated['theme_id']);

        $planId = $validated['vendor_subscription_plan_id'] ?? $this->defaultSubscriptionPlanId();

        DB::transaction(function () use ($requestedVendor, $theme, $validated, $normalizedEmail, $normalizedPhone, $normalizedDomain, $planId) {
            $tenant = Tenant::create([
                'name' => $requestedVendor->name,
                'source_requested_vendor_id' => $requestedVendor->id,
                'legal_business_name' => $requestedVendor->legal_business_name,
                'store_display_name' => $requestedVendor->store_display_name,
                'slug' => $this->generateUniqueTenantSlug($requestedVendor->name),
                'theme_id' => $theme->id,
                'status' => 'active',
                'vendor_subscription_plan_id' => $planId,
                'vendor_subscription_status' => $validated['vendor_subscription_status'] ?? 'active',
                'vendor_panel_enabled' => (bool) ($validated['vendor_panel_enabled'] ?? true),
                'vendor_subscription_started_at' => $planId ? now() : null,
                'address' => $requestedVendor->business_address,
                'country' => $requestedVendor->country,
                'country_code' => $requestedVendor->country_code,
                'search_location' => $requestedVendor->search_location,
                'google_place_id' => $requestedVendor->google_place_id,
                'postal_code' => $requestedVendor->postal_code,
                'address_line_1' => $requestedVendor->address_line_1,
                'address_line_2' => $requestedVendor->address_line_2,
                'state_province' => $requestedVendor->state_province,
                'city' => $requestedVendor->city,
                'contact' => $requestedVendor->phone,
                'business_email' => $requestedVendor->business_email,
                'owner_name' => $requestedVendor->owner_name,
                'business_registration_number' => $requestedVendor->business_registration_number,
                'website_order_types' => $this->websiteOrderTypesFromRequest($requestedVendor->service_options ?? []),
                'food_types' => $requestedVendor->food_types ?? [],
                'opening_from' => $requestedVendor->opening_from,
                'opening_to' => $requestedVendor->opening_to,
                'business_logo_path' => $requestedVendor->business_logo_path,
                'business_photo_paths' => $requestedVendor->business_photo_paths ?? [],
                'restaurant_page_image_paths' => $requestedVendor->restaurant_page_image_paths ?? [],
                'business_license_path' => $requestedVendor->business_license_path,
            ]);

            if ($requestedVendor->business_logo_path && Storage::disk('public')->exists($requestedVendor->business_logo_path)) {
                $tenant->addMediaFromDisk($requestedVendor->business_logo_path, 'public')
                    ->preservingOriginal()
                    ->toMediaCollection('VendorLogo');
            }

            Domain::create([
                'tenant_id' => $tenant->id,
                'domain' => $normalizedDomain,
                'is_primary' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $requestedVendor->admin_name,
                'email' => $normalizedEmail,
                'phone' => $normalizedPhone,
                'status' => 'active',
                'password' => Hash::make($validated['admin_password']),
            ]);

            $role = Role::where('guard_name', 'vendor')
                ->whereNull('tenant_id')
                ->firstOrFail();

            $user->assignRole($role);

            $requestedVendor->update([
                'status' => 'approved',
                'reason' => null,
            ]);
        });

        $requestedVendor->refresh();

        $this->sendStatusEmail($requestedVendor, [
            'domain_name' => $normalizedDomain,
            'theme_name' => $theme->name,
            'admin_password' => $validated['admin_password'],
        ]);

        return response()->json([
            'message' => 'Vendor request approved and vendor account created successfully.',
            'vendor' => $this->transformVendor($requestedVendor),
        ]);
    }

    public function reject(Request $request, RequestedVendor $requestedVendor)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $requestedVendor->update([
            'status' => 'rejected',
            'reason' => $validated['reason'] ?? null,
        ]);

        $requestedVendor->refresh();

        $this->sendStatusEmail($requestedVendor);

        return response()->json([
            'message' => 'Vendor request rejected successfully.',
            'vendor' => $this->transformVendor($requestedVendor),
        ]);
    }

    protected function sendStatusEmail(RequestedVendor $requestedVendor, array $approvalData = []): void
    {
        try {
            Mail::to($requestedVendor->email)->send(
                new VendorRequestStatusMail(
                    $requestedVendor,
                    route('seller.register.status', $requestedVendor->slug),
                    $approvalData
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send vendor status update email.', [
                'vendor_request_id' => $requestedVendor->id,
                'email' => $requestedVendor->email,
                'status' => $requestedVendor->status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function transformVendor(RequestedVendor $requestedVendor): array
    {
        return [
            'id' => $requestedVendor->id,
            'name' => $requestedVendor->name,
            'admin_name' => $requestedVendor->admin_name,
            'email' => $requestedVendor->email,
            'phone' => $requestedVendor->phone,
            'legal_business_name' => $requestedVendor->legal_business_name,
            'store_display_name' => $requestedVendor->store_display_name,
            'business_address' => $requestedVendor->business_address,
            'business_email' => $requestedVendor->business_email,
            'owner_name' => $requestedVendor->owner_name,
            'business_registration_number' => $requestedVendor->business_registration_number,
            'city' => $requestedVendor->city,
            'country' => $requestedVendor->country,
            'country_code' => $requestedVendor->country_code,
            'search_location' => $requestedVendor->search_location,
            'google_place_id' => $requestedVendor->google_place_id,
            'postal_code' => $requestedVendor->postal_code,
            'address_line_1' => $requestedVendor->address_line_1,
            'address_line_2' => $requestedVendor->address_line_2,
            'state_province' => $requestedVendor->state_province,
            'food_types' => $requestedVendor->food_types ?? [],
            'opening_from' => $requestedVendor->opening_from,
            'opening_to' => $requestedVendor->opening_to,
            'service_options' => $requestedVendor->service_options ?? [],
            'terms_accepted' => $requestedVendor->terms_accepted,
            'business_logo_url' => $requestedVendor->business_logo_path ? Storage::disk('public')->url($requestedVendor->business_logo_path) : null,
            'business_photo_urls' => collect($requestedVendor->business_photo_paths ?? [])
                ->map(fn ($path) => Storage::disk('public')->url($path))
                ->values()
                ->all(),
            'restaurant_page_image_urls' => collect($requestedVendor->restaurant_page_image_paths ?? [])
                ->mapWithKeys(fn ($path, $key) => [
                    $key => Str::startsWith($path, ['http://', 'https://'])
                        ? $path
                        : (Str::startsWith($path, ['vendor-requests/'])
                            ? Storage::disk('public')->url($path)
                            : asset(ltrim($path, '/'))),
                ])
                ->all(),
            'business_license_url' => $requestedVendor->business_license_path ? Storage::disk('public')->url($requestedVendor->business_license_path) : null,
            'status' => $requestedVendor->status,
            'slug' => $requestedVendor->slug,
            'reason' => $requestedVendor->reason,
            'created_at' => optional($requestedVendor->created_at)->format('d M Y, h:i A'),
            'updated_at' => optional($requestedVendor->updated_at)->format('d M Y, h:i A'),
            'available_subscription_plans' => $this->subscriptionPlanOptions(),
        ];
    }

    protected function defaultSubscriptionPlanId(): ?int
    {
        return VendorSubscriptionPlan::query()
            ->where('status', 'active')
            ->where('is_default', true)
            ->value('id');
    }

    protected function subscriptionPlanOptions()
    {
        return VendorSubscriptionPlan::query()
            ->where('status', 'active')
            ->select(['id', 'plan_name', 'plan_code', 'monthly_price', 'yearly_price', 'currency_code', 'auto_renew', 'is_default'])
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('plan_name')
            ->get()
            ->map(fn (VendorSubscriptionPlan $plan) => [
                'id' => $plan->id,
                'name' => "{$plan->plan_name} ({$plan->plan_code})",
                'label' => "{$plan->plan_name} - {$plan->currency_code} " . number_format((float) $plan->monthly_price, 2) . " / month" . ($plan->is_default ? ' (Default)' : ''),
                'plan_name' => $plan->plan_name,
                'plan_code' => $plan->plan_code,
                'monthly_price' => $plan->monthly_price,
                'yearly_price' => $plan->yearly_price,
                'currency_code' => $plan->currency_code,
                'auto_renew' => (bool) $plan->auto_renew,
                'is_default' => (bool) $plan->is_default,
            ])
            ->values();
    }

    protected function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone));
    }

    protected function websiteOrderTypesFromRequest(array $serviceOptions): array
    {
        return collect($serviceOptions)
            ->map(fn ($option) => Str::of($option)->lower()->replace([' ', '-'], '_')->toString())
            ->map(fn ($option) => match ($option) {
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

    protected function generateUniqueTenantSlug(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'vendor';
        }

        $slug = $base;
        $counter = 2;

        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
