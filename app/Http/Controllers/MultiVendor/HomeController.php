<?php

namespace App\Http\Controllers\MultiVendor;

use App\Models\Vehicle;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\FoodCategory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HomeController
{
    private const HOME_VENDOR_LIMIT = 8;
    private const HOME_CACHE_SECONDS = 300;
    private const HOME_FILTER_CACHE_SECONDS = 60;

    public function home(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:80'],
            'food_type' => ['nullable', 'string', 'max:80'],
            'delivery' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:80'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $foodType = trim((string) ($filters['food_type'] ?? ''));
        $location = trim((string) ($filters['location'] ?? ''));
        $country = trim((string) ($filters['country'] ?? ''));
        $delivery = trim((string) ($filters['delivery'] ?? ''));
        $websiteOrderType = $this->homeWebsiteOrderType($delivery);
        $vendorCacheKey = 'multivendor.home.vendors.' . md5(json_encode([
            'search' => $search,
            'food_type' => $foodType,
            'location' => $location,
            'country' => $country,
            'website_order_type' => $websiteOrderType,
        ]));

        [$vendors, $matchedVendors] = Cache::remember($vendorCacheKey, self::HOME_FILTER_CACHE_SECONDS, function () use ($search, $foodType, $location, $country, $websiteOrderType) {
            $vendorQuery = Tenant::query()
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
                    $nested->where('address', 'like', "%{$country}%")
                        ->orWhere('country', 'like', "%{$country}%")
                        ->orWhereHas('branches', function ($branchQuery) use ($country) {
                            $branchQuery->where('country', 'like', "%{$country}%");
                        });
                });
            })
            ->when($websiteOrderType !== null, function ($query) use ($websiteOrderType) {
                $query->whereJsonContains('website_order_types', $websiteOrderType);
            })
            ->latest();

            $matchedVendors = (clone $vendorQuery)->count();

            $vendors = $vendorQuery->limit(self::HOME_VENDOR_LIMIT)->get()
                ->map(function (Tenant $tenant) {
                    $primaryDomain = $tenant->domains->first();
                    $branch = $tenant->branches->first();
                    $categories = $tenant->categories->pluck('name')->values();

                    $address = $branch
                        ? collect([$branch->address_line_1, $branch->city, $branch->country])->filter()->join(', ')
                        : collect([$tenant->address_line_1, $tenant->address_line_2, $tenant->city, $tenant->state_province, $tenant->country])->filter()->join(', ');

                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'contact' => $tenant->contact ?: ($branch?->phone ?? ''),
                        'address' => $address ?: 'Sri Lanka',
                        'city' => $branch?->city ?: ($tenant->city ?: ''),
                        'categories' => $categories,
                        'product_count' => (int) $tenant->product_count,
                        'rating' => number_format(4.6 + (($tenant->id % 4) / 10), 1),
                        'delivery_time' => (25 + (($tenant->id % 4) * 5)) . '-' . (35 + (($tenant->id % 4) * 5)) . ' min',
                        'logo_url' => $tenant->getFirstMediaUrl('VendorLogo') ?: null,
                        'url' => $primaryDomain
                            ? (Str::startsWith($primaryDomain->domain, ['http://', 'https://'])
                                ? $primaryDomain->domain
                                : 'http://' . $primaryDomain->domain)
                            : null,
                    ];
                })
                ->values();

            return [$vendors, $matchedVendors];
        });

        $foodTypes = Cache::remember('multivendor.home.food_types', self::HOME_CACHE_SECONDS, fn () => $this->homeFoodTypes());

        $featuredProducts = Cache::remember('multivendor.home.featured_products', self::HOME_CACHE_SECONDS, fn () => $this->homeFeaturedProducts());

        $activeProductCount = Cache::remember(
            'multivendor.home.active_product_count',
            self::HOME_CACHE_SECONDS,
            fn () => Schema::hasTable('products') ? Product::query()->where('is_active', true)->count() : 0
        );

        return Inertia::render('MultiVendor/Home/Home', [
            'vendors' => $vendors,
            'foodTypes' => $foodTypes,
            'featuredProducts' => $featuredProducts,
            'filters' => [
                'search' => $search,
                'food_type' => $foodType,
                'delivery' => $delivery,
                'location' => $location,
                'country' => $country,
            ],
            'stats' => [
                'vendors' => $matchedVendors,
                'shown_vendors' => $vendors->count(),
                'food_types' => $foodTypes->count(),
                'products' => $activeProductCount,
            ],
        ]);
    }

    private function homeFoodTypes()
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

    private function homeWebsiteOrderType(string $delivery): ?string
    {
        return match (Str::of($delivery)->lower()->replace([' ', '-'], '_')->toString()) {
            'delivery' => 'delivery',
            'pickup', 'pick_up' => 'pickup',
            'scheduled', 'scheduled_orders' => 'scheduled',
            default => null,
        };
    }

    private function homeFeaturedProducts()
    {
        if (! Schema::hasTable('products')) {
            return collect();
        }

        return Product::query()
            ->select(['id', 'tenant_id', 'name', 'description', 'image_path', 'base_price', 'is_active', 'created_at'])
            ->with('categories:id,name,slug')
            ->where('is_active', true)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'tenant_id' => $product->tenant_id,
                'name' => $product->name,
                'description' => Str::limit((string) $product->description, 90),
                'image_url' => $product->image_url,
                'price' => (float) $product->base_price,
                'categories' => $product->categories->pluck('name')->values(),
            ])
            ->values();
    }

    public function brands()
    {
        return response()->json(
            VehicleBrand::query()
                ->where('status', 1)
                ->orderBy('title')
                ->get(['id', 'title'])
        );
    }

    public function models(Request $request)
    {
        $data = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:vehicle_brands,id'],
        ]);

        return response()->json(
            VehicleModel::query()
                ->where('status', 1)
                ->where('brand_id', $data['brand_id'])
                ->orderBy('title')
                ->get(['id', 'brand_id', 'title'])
        );
    }

    public function types()
    {
        return response()->json(
            VehicleType::query()
                ->where('status', 1)
                ->orderBy('title')
                ->get(['id', 'title'])
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'title' => $t->title,
                        'image_url' => data_get($t, 'image_url') ?: data_get($t, 'image'),
                    ];
                })
                ->values()
        );
    }

    public function vehicles(Request $request)
    {
        $data = $request->validate([
            'featured'   => ['nullable', 'in:0,1'],
            'type_id'    => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'brand_id'   => ['nullable', 'integer', 'exists:vehicle_brands,id'],
            'model_id'   => ['nullable', 'integer', 'exists:vehicle_models,id'],
            'year'       => ['nullable', 'integer'],
            'condition'  => ['nullable', 'string'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $perPage = (int)($data['per_page'] ?? 6);

        $query = Vehicle::query()
            ->with([
                'manufacture:id,title',
                'vehicleModel:id,title',
                'vehicleType:id,title',
                'media',
            ])
            ->where('status', 1)
            ->when(isset($data['featured']), fn($q) => $q->where('featured', (int)$data['featured']))
            ->when($data['type_id'] ?? null, fn($q, $v) => $q->where('vehicle_type_id', $v))
            ->when($data['brand_id'] ?? null, fn($q, $v) => $q->where('manufacture_id', $v))
            ->when($data['model_id'] ?? null, fn($q, $v) => $q->where('vehicle_model_id', $v))
            ->when($data['year'] ?? null, fn($q, $v) => $q->where('year', $v))
            ->when($data['condition'] ?? null, fn($q, $v) => $q->where('condition', $v))
            ->latest();

        return response()->json(
            $query->paginate($perPage)->through(function ($v) {
                return [
                    'id'       => $v->id,
                    'image'    => $v->image_url,
                    'year'     => (int) $v->year,
                    'make'     => $v->manufacture?->title,
                    'model'    => $v->vehicleModel?->title,
                    'type'     => $v->vehicleType?->title,
                    'mileage'  => (int) ($v->mileage ?? 0),
                    'fuel'     => $v->FuelTypeString,
                    'featured' => (bool) $v->featured,
                    'price'    => $v->price ?? null,
                ];
            })
        );
    }

    public function brandNewVehicles()
    {
        $vehicles = Vehicle::query()
            ->with([
                'manufacture:id,title',
                'vehicleModel:id,title',
                'vehicleType:id,title',
                'city:id,name',
                'media',
            ])
            ->where([
                'status' => 1,
                'condition' => 'Brand New'
            ])
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($v) {
                return [
                    'id'       => $v->id,
                    'image'    => $v->image_url,
                    'year'     => (int) $v->year,
                    'make'     => $v->manufacture?->title,
                    'model'    => $v->vehicleModel?->title,
                    'type'     => $v->vehicleType?->title,
                    'mileage'  => (int) ($v->mileage ?? 0),
                    'fuel'     => $v->FuelTypeString,
                    'featured' => (bool) $v->featured,
                    'price'    => $v->price ?? null,
                    'city'     => $v->city?->name,
                ];
            });

        return response()->json($vehicles);
    }

    public function usedVehicles()
    {
        $vehicles = Vehicle::query()
            ->with([
                'manufacture:id,title',
                'vehicleModel:id,title',
                'vehicleType:id,title',
                'city:id,name',
                'media',
            ])
            ->where([
                'status' => 1,
                'condition' => 'Used'
            ])
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($v) {
                return [
                    'id'       => $v->id,
                    'image'    => $v->image_url,
                    'year'     => (int) $v->year,
                    'make'     => $v->manufacture?->title,
                    'model'    => $v->vehicleModel?->title,
                    'type'     => $v->vehicleType?->title,
                    'mileage'  => (int) ($v->mileage ?? 0),
                    'fuel'     => $v->FuelTypeString,
                    'featured' => (bool) $v->featured,
                    'price'    => $v->price ?? null,
                    'city'     => $v->city?->name,
                ];
            });

        return response()->json($vehicles);
    }
}
