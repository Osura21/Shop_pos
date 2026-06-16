<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Models\SeoFooterLink;
use App\Models\Tenant;
use App\Models\TenantCurrencySetting;
use App\Support\CurrencyRegistry;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $host = $request->getHost();
        $mainDomain = config('app.main_domain');

        $isAdminContext = ($host === $mainDomain) && $request->is('admin*');

        $user = null;
        $permissions = [];
        $guard = null;
        $currencySettings = null;
        $vendorSubscription = null;

        if ($isAdminContext) {
            if (Auth::guard('superadmin')->check()) {
                $user = Auth::guard('superadmin')->user();
                $permissions = $user->getAllPermissions()->pluck('name');
                $guard = 'superadmin';
            }
        } else {
            if (Auth::guard('customer')->check()) {
                $user = Auth::guard('customer')->user();

                if ($user) {
                    $user->load('phoneNumbers');
                }

                $permissions = [];
                $guard = 'customer';
            } elseif (Auth::guard('vendor')->check()) {
                $user = Auth::guard('vendor')->user();
                $permissions = $user->getAllPermissions()->pluck('name');
                $guard = 'vendor';

                $currencySettings = $this->resolveVendorCurrencySettings($user->tenant_id ?? null);
                $vendorSubscription = $this->resolveVendorSubscription($user->tenant_id ?? null);
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'permissions' => $permissions,
                'guard' => $guard,
            ],
            'flash' => [
                'message' => fn() => $request->session()->get('message')
                    ?? $request->session()->get('success'),

                'error' => fn() => $request->session()->get('error'),
            ],
            'currencySettings' => $currencySettings,
            'vendorSubscription' => $vendorSubscription,
            'seoFooterSections' => fn () => $isAdminContext ? [] : $this->seoFooterSections(),
        ]);
    }

    private function seoFooterSections(): array
    {
        if (! Schema::hasTable('seo_footer_links')) {
            return [];
        }

        return Cache::remember('multivendor.seo_footer_sections', 300, function () {
            return SeoFooterLink::query()
                ->select(['country', 'country_code', 'location', 'link_text', 'food_type', 'food_type_slug', 'order_type', 'sort_order'])
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('country')
                ->orderBy('location')
                ->get()
                ->groupBy(fn (SeoFooterLink $link) => $this->cleanCountryName($link->country))
                ->map(fn ($links, string $country) => [
                    'country' => $country,
                    'country_code' => $links->first()->country_code,
                    'cities' => $links
                        ->groupBy('location')
                        ->map(fn ($cityLinks, string $location) => [
                            'location' => $location,
                            'links' => $cityLinks
                                ->map(fn (SeoFooterLink $link) => [
                                    'label' => $this->seoFooterLinkLabel($link),
                                    'location' => $link->location,
                                    'food_type' => $link->food_type_slug,
                                    'delivery' => $link->order_type,
                                    'country' => $this->cleanCountryName($link->country),
                                ])
                                ->values()
                                ->all(),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all();
        });
    }

    private function seoFooterLinkLabel(SeoFooterLink $link): string
    {
        if (filled($link->link_text)) {
            return $link->link_text;
        }

        $orderType = match ($link->order_type) {
            'pickup' => 'Pickup',
            'scheduled' => 'Scheduled',
            default => 'Delivery',
        };

        return "{$link->food_type} {$orderType} in {$link->location}";
    }

    private function cleanCountryName(?string $country): string
    {
        return trim(preg_replace('/\s*\([^)]*\)\s*/', '', (string) $country));
    }

    private function resolveVendorSubscription(?int $tenantId): ?array
    {
        if (!$tenantId) {
            return null;
        }

        $tenant = Tenant::query()
            ->with(['vendorSubscriptionPlan.features'])
            ->find($tenantId);

        $plan = $tenant?->vendorSubscriptionPlan;

        if (!$tenant || !$plan) {
            return null;
        }

        $enabledFeatures = $plan->features
            ->where('enabled', true)
            ->pluck('feature_key')
            ->values();

        return [
            'plan_id' => $plan->id,
            'plan_name' => $plan->plan_name,
            'plan_code' => $plan->plan_code,
            'status' => $tenant->vendor_subscription_status,
            'enabled_features' => $enabledFeatures,
            'limits' => $plan->features
                ->where('value_type', 'limit')
                ->mapWithKeys(fn ($feature) => [
                    $feature->feature_key => [
                        'enabled' => (bool) $feature->enabled,
                        'is_unlimited' => (bool) $feature->is_unlimited,
                        'limit_value' => $feature->limit_value,
                        'unit' => $feature->unit,
                    ],
                ])
                ->all(),
        ];
    }

    private function resolveVendorCurrencySettings(?int $tenantId): ?array
    {
        if (!$tenantId) {
            return null;
        }

        $setting = TenantCurrencySetting::query()
            ->where('tenant_id', $tenantId)
            ->first();

        $baseCode = $setting?->base_currency_code ?: 'LKR';
        $secondaryCode = $setting?->secondary_currency_code;

        return [
            'base_currency' => CurrencyRegistry::find($baseCode),
            'secondary_currency' => CurrencyRegistry::find($secondaryCode),
            'available' => CurrencyRegistry::all(),
        ];
    }
}
