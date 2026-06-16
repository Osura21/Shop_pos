<?php

namespace App\Http\Controllers\MultiVendor;

use App\Models\Category;
use App\Models\FoodCategory;
use App\Models\SeoFooterLink;
use App\Models\Vehicle;
use Inertia\Inertia;
use App\Models\District;
use App\Models\City;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Models\SubscriptionPlan;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PageController
{
    private const RESTAURANT_CACHE_SECONDS = 60;

    public function about()
    {
        return Inertia::render('MultiVendor/About/Index');
    }

    public function contact()
    {
        return Inertia::render('MultiVendor/Contact/Index');
    }

    public function features()
    {
        return Inertia::render('MultiVendor/Features/Index');
    }

    public function pricing()
    {
        $plans = VendorSubscriptionPlan::query()
            ->with('features')
            ->where('status', 'active')
            ->orderBy('display_order')
            ->orderBy('monthly_price')
            ->get()
            ->map(fn (VendorSubscriptionPlan $plan) => $this->pricingPlanPayload($plan))
            ->values();

        $featureGroups = $plans
            ->flatMap(fn (array $plan) => $plan['features'])
            ->groupBy('group')
            ->map(fn ($features, string $group) => [
                'name' => $group,
                'features' => $features
                    ->unique('key')
                    ->map(fn ($feature) => [
                        'key' => $feature['key'],
                        'name' => $feature['name'],
                    ])
                    ->values(),
            ])
            ->values();

        return Inertia::render('MultiVendor/Pricing/Index', [
            'plans' => $plans,
            'featureGroups' => $featureGroups,
        ]);
    }

    public function whyUs()
    {
        return Inertia::render('MultiVendor/WhyUs/Index');
    }

    public function restaurants(Request $request, ?string $filters = null)
    {
        $queryFilters = $request->validate([
            'search' => ['nullable', 'string', 'max:80'],
            'food_type' => ['nullable', 'string', 'max:80'],
            'delivery' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:3', 'max:18'],
        ]);

        $filters = array_merge(
            $this->restaurantFiltersFromPath($filters),
            array_filter($queryFilters, fn ($value) => $value !== null && $value !== '')
        );

        $search = trim((string) ($filters['search'] ?? ''));
        $foodType = trim((string) ($filters['food_type'] ?? ''));
        $delivery = trim((string) ($filters['delivery'] ?? ''));
        $location = trim((string) ($filters['location'] ?? ''));
        $country = trim((string) ($filters['country'] ?? ''));
        $websiteOrderType = $this->restaurantWebsiteOrderType($delivery);
        $perPage = (int) ($filters['per_page'] ?? 9);

        $restaurants = $this->restaurantQuery($search, $foodType, $location, $country, $websiteOrderType)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Tenant $tenant) => $this->restaurantPayload($tenant));

        $foodTypes = Cache::remember(
            'multivendor.restaurants.food_types',
            300,
            fn () => $this->restaurantFoodTypes()
        );

        return Inertia::render('MultiVendor/Restaurants/Index', [
            'restaurants' => $restaurants,
            'foodTypes' => $foodTypes,
            'filters' => [
                'search' => $search,
                'food_type' => $foodType,
                'delivery' => $delivery,
                'location' => $location,
                'country' => $country,
            ],
            'stats' => [
                'restaurants' => $restaurants->total(),
            ],
        ]);
    }

    private function restaurantFiltersFromPath(?string $path): array
    {
        $segments = collect(explode('/', trim((string) $path, '/')))
            ->map(fn ($segment) => trim(rawurldecode($segment)))
            ->filter()
            ->values();

        if ($segments->isEmpty()) {
            return [];
        }

        $filters = [];
        $services = ['delivery', 'pickup', 'scheduled'];
        $foodSlugs = $this->restaurantFoodTypes()->pluck('slug')->filter()->values()->all();
        $countries = Cache::remember('multivendor.restaurants.seo_countries', 300, function () {
            return SeoFooterLink::query()
                ->where('is_active', true)
                ->distinct()
                ->pluck('country')
                ->filter()
                ->mapWithKeys(fn ($country) => [Str::slug($country) => $country])
                ->all();
        });

        for ($index = 0; $index < $segments->count(); $index++) {
            $segment = Str::slug($segments[$index]);

            if ($segment === 'search') {
                $filters['search'] = $this->humanizeRestaurantSlug($segments->slice($index + 1)->implode(' '));
                break;
            }

            foreach ($services as $service) {
                if ($segment === $service) {
                    $filters['delivery'] = $service;
                    continue 2;
                }

                if (Str::endsWith($segment, "-{$service}")) {
                    $filters['delivery'] = $service;
                    $candidate = Str::beforeLast($segment, "-{$service}");

                    if (in_array($candidate, $foodSlugs, true)) {
                        $filters['food_type'] = $candidate;
                    } elseif ($candidate !== '' && empty($filters['location'])) {
                        $filters['location'] = $this->humanizeRestaurantSlug($candidate);
                    }

                    continue 2;
                }
            }

            if (isset($countries[$segment]) && empty($filters['country'])) {
                $filters['country'] = $countries[$segment];
                continue;
            }

            if (in_array($segment, $foodSlugs, true) && empty($filters['food_type'])) {
                $filters['food_type'] = $segment;
                continue;
            }

            if (empty($filters['country']) && $segments->count() >= 3 && $index === 0) {
                $filters['country'] = $this->humanizeRestaurantSlug($segment);
                continue;
            }

            if (empty($filters['location'])) {
                $filters['location'] = $this->humanizeRestaurantSlug($segment);
                continue;
            }

            if (empty($filters['search'])) {
                $filters['search'] = $this->humanizeRestaurantSlug($segment);
            }
        }

        return $filters;
    }

    private function humanizeRestaurantSlug(string $value): string
    {
        return Str::of($value)
            ->replace(['-', '_'], ' ')
            ->squish()
            ->title()
            ->toString();
    }

    private function restaurantQuery(string $search, string $foodType, string $location, string $country, ?string $websiteOrderType)
    {
        $cacheKey = 'multivendor.restaurants.ids.' . md5(json_encode([
            'search' => $search,
            'food_type' => $foodType,
            'location' => $location,
            'country' => $country,
            'website_order_type' => $websiteOrderType,
        ]));

        $ids = Cache::remember($cacheKey, self::RESTAURANT_CACHE_SECONDS, function () use ($search, $foodType, $location, $country, $websiteOrderType) {
            return Tenant::query()
                ->select('id')
                ->where('status', 'active')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested->where('name', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%")
                            ->orWhere('address_line_1', 'like', "%{$search}%")
                            ->orWhere('address_line_2', 'like', "%{$search}%")
                            ->orWhere('city', 'like', "%{$search}%")
                            ->orWhere('state_province', 'like', "%{$search}%")
                            ->orWhere('country', 'like', "%{$search}%")
                            ->orWhere('postal_code', 'like', "%{$search}%")
                            ->orWhere('contact', 'like', "%{$search}%")
                            ->orWhereHas('branches', function ($branchQuery) use ($search) {
                                $branchQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('city', 'like', "%{$search}%")
                                    ->orWhere('country', 'like', "%{$search}%")
                                    ->orWhere('address_line_1', 'like', "%{$search}%");
                            })
                            ->orWhereHas('categories', function ($categoryQuery) use ($search) {
                                $categoryQuery->where('name', 'like', "%{$search}%")
                                    ->orWhereHas('foodCategory', function ($foodCategoryQuery) use ($search) {
                                        $foodCategoryQuery->where('name', 'like', "%{$search}%");
                                    });
                            });
                    });
                })
                ->when($foodType !== '', function ($query) use ($foodType) {
                    $query->whereHas('categories', function ($categoryQuery) use ($foodType) {
                        $categoryQuery->where('is_active', true)
                            ->whereHas('foodCategory', function ($foodCategoryQuery) use ($foodType) {
                                $foodCategoryQuery->where('is_active', true)
                                    ->where(function ($nested) use ($foodType) {
                                        $nested->where('slug', $foodType)
                                            ->orWhere('name', 'like', str_replace('-', ' ', "%{$foodType}%"));
                                    });
                        });
                    });
                })
                ->when($location !== '', function ($query) use ($location) {
                    $query->where(function ($nested) use ($location) {
                        $nested->where('address', 'like', "%{$location}%")
                            ->orWhere('address_line_1', 'like', "%{$location}%")
                            ->orWhere('address_line_2', 'like', "%{$location}%")
                            ->orWhere('city', 'like', "%{$location}%")
                            ->orWhere('state_province', 'like', "%{$location}%")
                            ->orWhere('country', 'like', "%{$location}%")
                            ->orWhere('postal_code', 'like', "%{$location}%")
                            ->orWhereHas('branches', function ($branchQuery) use ($location) {
                                $branchQuery->where('city', 'like', "%{$location}%")
                                    ->orWhere('country', 'like', "%{$location}%")
                                    ->orWhere('address_line_1', 'like', "%{$location}%");
                            });
                    });
                })
                ->when($country !== '', function ($query) use ($country) {
                    $query->where(function ($nested) use ($country) {
                        $nested->where('country', $country)
                            ->orWhere('country', 'like', "%{$country}%")
                            ->orWhereHas('branches', function ($branchQuery) use ($country) {
                                $branchQuery->where('country', $country)
                                    ->orWhere('country', 'like', "%{$country}%");
                            });
                    });
                })
                ->when($websiteOrderType !== null, function ($query) use ($websiteOrderType) {
                    $query->whereJsonContains('website_order_types', $websiteOrderType);
                })
                ->latest()
                ->pluck('id')
                ->all();
        });

        return Tenant::query()
            ->select(['id', 'name', 'slug', 'status', 'address', 'country', 'address_line_1', 'address_line_2', 'state_province', 'city', 'postal_code', 'contact', 'created_at'])
            ->with([
                'media',
                'domains' => fn ($query) => $query
                    ->select(['id', 'tenant_id', 'domain', 'is_primary'])
                    ->orderByDesc('is_primary')
                    ->orderBy('id'),
                'branches' => fn ($query) => $query
                    ->select(['id', 'tenant_id', 'name', 'phone', 'city', 'country', 'address_line_1', 'is_active'])
                    ->where('is_active', true)
                    ->orderBy('id'),
                'categories' => fn ($query) => $query
                    ->select(['categories.id', 'categories.tenant_id', 'categories.food_category_id', 'categories.name', 'categories.sort_order', 'categories.is_active'])
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name'),
            ])
            ->withCount([
                'products as product_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->whereIn('id', $ids ?: [0])
            ->latest();
    }

    private function restaurantPayload(Tenant $tenant): array
    {
        $primaryDomain = $tenant->domains->first();
        $branch = $tenant->branches->first();
        $category = $tenant->categories->first();

        $logoUrl = $tenant->getFirstMediaUrl('VendorLogo') ?: null;

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'type' => Str::slug($category?->name ?: 'restaurant'),
            'type_label' => $category?->name ?: 'Restaurant',
            'rating' => number_format(4.4 + (($tenant->id % 6) / 10), 1),
            'location' => $branch?->city ?: ($tenant->city ?: ($tenant->address ?: 'Sri Lanka')),
            'time' => (15 + (($tenant->id % 4) * 5)) . ' - ' . (25 + (($tenant->id % 4) * 5)) . ' min',
            'price' => 'Rs. ' . (280 + (($tenant->id % 6) * 70)) . '+',
            'desc' => $category
                ? Str::limit("Fresh {$category->name} favorites, fast ordering, and reliable service.", 96)
                : Str::limit('Fresh meals, quick ordering, delivery, pickup, and customer-friendly service.', 96),
            'image_url' => $logoUrl,
            'has_logo' => $logoUrl !== null,
            'url' => $primaryDomain
                ? (Str::startsWith($primaryDomain->domain, ['http://', 'https://'])
                    ? $primaryDomain->domain
                    : 'http://' . $primaryDomain->domain)
                : null,
        ];
    }

    private function restaurantFoodTypes()
    {
        if (! Schema::hasTable('food_categories')) {
            return collect();
        }

        $categoryCounts = Category::query()
            ->select('food_category_id', DB::raw('COUNT(DISTINCT tenant_id) as vendor_count'))
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->whereNotNull('food_category_id')
            ->groupBy('food_category_id')
            ->pluck('vendor_count', 'food_category_id');

        return FoodCategory::query()
            ->with('media')
            ->select(['id', 'name', 'slug', 'sort_order', 'is_active'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(24)
            ->get()
            ->map(fn (FoodCategory $category) => [
                'name' => $category->name,
                'slug' => $category->slug ?: Str::slug($category->name),
                'count' => (int) ($categoryCounts[$category->id] ?? 0),
                'image_url' => $category->image_url,
            ])
            ->values();
    }

    private function restaurantWebsiteOrderType(string $delivery): ?string
    {
        return match (Str::of($delivery)->lower()->replace([' ', '-'], '_')->toString()) {
            'delivery' => 'delivery',
            'pickup', 'pick_up' => 'pickup',
            'scheduled', 'scheduled_orders' => 'scheduled',
            default => null,
        };
    }

    private function pricingPlanPayload(VendorSubscriptionPlan $plan): array
    {
        $features = $plan->features
            ->map(fn ($feature) => [
                'key' => $feature->feature_key,
                'name' => $feature->feature_name,
                'group' => $feature->feature_group ?: 'Features',
                'enabled' => (bool) $feature->enabled,
                'value_type' => $feature->value_type,
                'is_unlimited' => (bool) $feature->is_unlimited,
                'limit_value' => $feature->limit_value !== null ? (float) $feature->limit_value : null,
                'unit' => $feature->unit,
                'notes' => $feature->notes,
                'display_value' => $this->pricingFeatureValue($feature),
            ])
            ->values();

        return [
            'id' => $plan->id,
            'name' => $plan->plan_name,
            'code' => $plan->plan_code,
            'description' => $plan->short_description,
            'monthly_price' => (float) $plan->monthly_price,
            'yearly_price' => $plan->yearly_price !== null ? (float) $plan->yearly_price : null,
            'currency_code' => $plan->currency_code ?: 'LKR',
            'trial_days' => (int) $plan->trial_days,
            'badge' => $plan->badge,
            'highlight_plan' => (bool) $plan->highlight_plan,
            'most_popular' => (bool) $plan->most_popular,
            'features' => $features,
        ];
    }

    private function pricingFeatureValue($feature): string
    {
        if (! $feature->enabled) {
            return 'Not included';
        }

        if ($feature->is_unlimited) {
            return 'Unlimited';
        }

        if ($feature->value_type === 'limit' && $feature->limit_value !== null) {
            $value = rtrim(rtrim(number_format((float) $feature->limit_value, 2), '0'), '.');

            return trim($value . ' ' . ($feature->unit ?: ''));
        }

        return 'Included';
    }
    public function postAd(Request $request)
    {
        $brandId = $request->query('brand_id');

        $manufacturers = VehicleBrand::query()
            ->where('status', true)
            ->orderBy('title')
            ->get(['id', 'title']);

        $modelsQ = VehicleModel::query()
            ->where('status', true);

        if ($brandId) {
            $modelsQ->where('brand_id', $brandId);
        }

        $models = $modelsQ->orderBy('title')->get(['id', 'title', 'brand_id']);

        $bodyTypes = VehicleType::query()
            ->where('status', true)
            ->orderBy('title')
            ->get(['id', 'title']);

       $customer = Auth::guard('customer')->user();

if ($customer) {
    $customer->load([
        'defaultPlan.planFeatures',
        'subscribedPlan.planFeatures',
    ]);
}

$defaultPlan = SubscriptionPlan::query()
    ->with('planFeatures')
    ->where('is_default', 1)
    ->where('status', 'active')
    ->first();

$effectivePlan = null;

if ($customer?->subscribedPlan && $customer->subscribedPlan->status === 'active') {
    $effectivePlan = $customer->subscribedPlan;
} elseif ($customer?->defaultPlan && $customer->defaultPlan->status === 'active') {
    $effectivePlan = $customer->defaultPlan;
} else {
    $effectivePlan = $defaultPlan;
}

// If feature missing => unlimited
$adFeature = $effectivePlan?->planFeatures?->firstWhere('feature_key', 'ad_post');

$limitType = $adFeature?->limit_type ?? 'unlimited';
$limitValue = null;

if ($limitType === 'limited') {
    $limitValue = (int) ($adFeature->limit_value ?? 0);
}

$usedCount = 0;
$canPostAd = true;

if ($customer) {
    $usedCount = Vehicle::query()
        ->where('customer_id', $customer->id)
        ->whereNull('deleted_at')
        ->count();

    if ($limitType === 'limited') {
        $canPostAd = $usedCount < $limitValue;
    }
}


        return Inertia::render('MultiVendor/PostAd/Index', [
            'manufacturers' => $manufacturers,
            'models' => $models,
            'bodyTypes' => $bodyTypes,
            'districts' => District::select('id', 'name')->orderBy('name')->get(),
            'citiesAll' => City::select('id', 'name', 'district_id')->orderBy('name')->get(),
            'requestQuery' => [
                'brand_id' => $brandId,
            ],
           'membershipPlan' => $effectivePlan ? [
    'id' => $effectivePlan->id,
    'subscription_name' => $effectivePlan->subscription_name,
    'subscription_plan_code' => $effectivePlan->subscription_plan_code,
    'price' => $effectivePlan->price,
    'billing_interval' => $effectivePlan->billing_interval,
] : null,

            'adPosting' => [
                'feature_key' => 'ad_post',
                'limit_type' => $limitType ?? 'unlimited',
                'limit_value' => $limitValue, // null if unlimited
                'used' => $usedCount,
                'can_post' => $canPostAd,
            ],
        ]);
    }
}
