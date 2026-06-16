<?php


//VendorSites/ThemeStockController
namespace App\Http\Controllers\VendorSites;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ThemeStockController extends Controller
{
    protected function tenantOrFail()
    {
        $tenant = tenant();

        abort_if(!$tenant, 404, 'Tenant not found');

        return $tenant;
    }

    protected function themePath(): string
    {
        return $this->tenantOrFail()->theme->path ?? 'Themes/classic';
    }

    protected function baseVehicleQuery()
    {
        $tenant = $this->tenantOrFail();

        return Vehicle::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 1);
    }

    protected function conditions(): array
    {
        return [
            ['slug' => 'brand-new', 'name' => 'Brand New'],
            ['slug' => 'used', 'name' => 'Used'],
            ['slug' => 'reconditioned', 'name' => 'Reconditioned'],
        ];
    }

    protected function resolveCondition(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = (string) Str::of($value)->trim()->lower();

        return match ($normalized) {
            'brand-new', 'brand new' => 'Brand New',
            'used' => 'Used',
            'reconditioned' => 'Reconditioned',
            default => collect($this->conditions())
                ->first(fn ($condition) => Str::lower($condition['name']) === $normalized)['name'] ?? null,
        };
    }

    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'featured'    => ['nullable', 'in:0,1'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'city_id'     => ['nullable', 'integer', 'exists:cities,id'],
            'type_id'     => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'brand_id'    => ['nullable', 'integer', 'exists:vehicle_brands,id'],
            'model_id'    => ['nullable', 'integer', 'exists:vehicle_models,id'],
            'condition'   => ['nullable', 'string'],
            'year_from'   => ['nullable', 'integer', 'min:1900'],
            'year_to'     => ['nullable', 'integer', 'min:1900'],
            'min_price'   => ['nullable', 'numeric', 'min:0'],
            'max_price'   => ['nullable', 'numeric', 'min:0'],
            'sortBy'      => ['nullable', 'in:newest,oldest'],
            'page'        => ['nullable', 'integer'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);
    }

    protected function applyVehicleFilters($query, array $filters)
    {
        $condition = $this->resolveCondition($filters['condition'] ?? null);

        return $query
            ->when(array_key_exists('featured', $filters) && $filters['featured'] !== null, fn ($q) => $q->where('featured', (int) $filters['featured']))
            ->when($filters['district_id'] ?? null, fn ($q, $value) => $q->where('district_id', $value))
            ->when($filters['city_id'] ?? null, fn ($q, $value) => $q->where('city_id', $value))
            ->when($filters['type_id'] ?? null, fn ($q, $value) => $q->where('vehicle_type_id', $value))
            ->when($filters['brand_id'] ?? null, fn ($q, $value) => $q->where('manufacture_id', $value))
            ->when($filters['model_id'] ?? null, fn ($q, $value) => $q->where('vehicle_model_id', $value))
            ->when($condition, fn ($q, $value) => $q->where('condition', $value))
            ->when($filters['year_from'] ?? null, fn ($q, $value) => $q->where('year', '>=', $value))
            ->when($filters['year_to'] ?? null, fn ($q, $value) => $q->where('year', '<=', $value))
            ->when($filters['min_price'] ?? null, fn ($q, $value) => $q->where('price', '>=', $value))
            ->when($filters['max_price'] ?? null, fn ($q, $value) => $q->where('price', '<=', $value));
    }

    protected function sharedFilterProps(): array
    {
        $districts = District::select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(function ($district) {
                $district->slug = Str::slug($district->name);
                return $district;
            });

        $districtSlugSet = $districts->pluck('slug')->flip();

        $usedCitySlugs = [];
        $citiesAll = City::select(['id', 'name', 'district_id'])
            ->orderBy('name')
            ->get()
            ->map(function ($city) use ($districtSlugSet, &$usedCitySlugs) {
                $baseSlug = Str::slug($city->name);
                $slug = $baseSlug;

                if (isset($districtSlugSet[$slug])) {
                    $slug .= '-city';
                }

                while (isset($usedCitySlugs[$slug])) {
                    $slug = $baseSlug . '-' . $city->id;
                }

                $usedCitySlugs[$slug] = true;
                $city->slug = $slug;

                return $city;
            });

        $lowestYear = (int) (
            $this->baseVehicleQuery()
                ->whereNotNull('year')
                ->where('year', '>=', 1900)
                ->min('year') ?? date('Y')
        );

        return [
            'districts' => $districts->values(),
            'citiesAll' => $citiesAll->values(),
            'conditions' => $this->conditions(),
            'lowestVehicleYear' => $lowestYear,
            'currentYear' => (int) date('Y'),
            'totalVehicleCount' => $this->baseVehicleQuery()->count(),
        ];
    }

    protected function frontendFilters(array $filters): array
    {
        $condition = $this->resolveCondition($filters['condition'] ?? null);

        return [
            'featured' => $filters['featured'] ?? null,
            'district_id' => $filters['district_id'] ?? null,
            'city_id' => $filters['city_id'] ?? null,
            'type_id' => $filters['type_id'] ?? null,
            'brand_id' => $filters['brand_id'] ?? null,
            'model_id' => $filters['model_id'] ?? null,
            'condition' => $condition,
            'condition_slug' => $condition ? Str::slug($condition) : null,
            'year_from' => $filters['year_from'] ?? null,
            'year_to' => $filters['year_to'] ?? null,
            'min_price' => $filters['min_price'] ?? null,
            'max_price' => $filters['max_price'] ?? null,
            'sortBy' => $filters['sortBy'] ?? null,
            'page' => $filters['page'] ?? null,
            'per_page' => $filters['per_page'] ?? null,
        ];
    }

    protected function resolveActiveSelections(array &$filters): array
    {
        $activeType = !empty($filters['type_id'])
            ? VehicleType::where('status', 1)->select(['id', 'title'])->find($filters['type_id'])
            : null;

        $activeDistrict = !empty($filters['district_id'])
            ? District::select(['id', 'name'])->find($filters['district_id'])
            : null;

        $activeCity = !empty($filters['city_id'])
            ? City::select(['id', 'name', 'district_id'])->find($filters['city_id'])
            : null;

        $activeBrand = !empty($filters['brand_id'])
            ? VehicleBrand::where('status', 1)->select(['id', 'title'])->find($filters['brand_id'])
            : null;

        $activeModel = !empty($filters['model_id'])
            ? VehicleModel::where('status', 1)->select(['id', 'title', 'brand_id'])->find($filters['model_id'])
            : null;

        if ($activeModel && !$activeBrand) {
            $activeBrand = VehicleBrand::where('status', 1)
                ->select(['id', 'title'])
                ->find($activeModel->brand_id);

            if ($activeBrand) {
                $filters['brand_id'] = $activeBrand->id;
            }
        }

        if ($activeBrand && $activeModel && (int) $activeModel->brand_id !== (int) $activeBrand->id) {
            $activeModel = null;
            $filters['model_id'] = null;
        }

        $activeConditionName = $this->resolveCondition($filters['condition'] ?? null);

        return [
            'type' => $activeType ? [
                'id' => $activeType->id,
                'title' => $activeType->title,
                'slug' => Str::slug($activeType->title),
            ] : null,

            'district' => $activeDistrict ? [
                'id' => $activeDistrict->id,
                'name' => $activeDistrict->name,
                'slug' => Str::slug($activeDistrict->name),
            ] : null,

            'city' => $activeCity ? [
                'id' => $activeCity->id,
                'name' => $activeCity->name,
                'slug' => Str::slug($activeCity->name),
            ] : null,

            'brand' => $activeBrand ? [
                'id' => $activeBrand->id,
                'title' => $activeBrand->title,
                'slug' => Str::slug($activeBrand->title),
            ] : null,

            'model' => $activeModel ? [
                'id' => $activeModel->id,
                'title' => $activeModel->title,
                'slug' => Str::slug($activeModel->title),
            ] : null,

            'condition' => $activeConditionName ? [
                'id' => $activeConditionName,
                'name' => $activeConditionName,
                'slug' => Str::slug($activeConditionName),
            ] : null,
        ];
    }

    protected function mapVehicleCard(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'slug' => $vehicle->seo_url,
            'seo_url' => $vehicle->seo_url,
            'year' => (int) ($vehicle->year ?? 0),
            'price' => $vehicle->price ?? null,
            'price_currency' => $vehicle->price_currency ?? 'LKR',
            'mileage' => (int) ($vehicle->mileage ?? 0),
            'condition' => $vehicle->condition,
            'transmission' => $vehicle->transmission,
            'fuel_type' => $vehicle->fuel_type,
            'engine_capacity' => $vehicle->engine_capacity,
            'availability' => $vehicle->availability,
            'featured' => (bool) $vehicle->featured,

            'manufacture' => $vehicle->manufacture ? [
                'id' => $vehicle->manufacture->id,
                'title' => $vehicle->manufacture->title,
            ] : null,

            'vehicle_model' => $vehicle->vehicleModel ? [
                'id' => $vehicle->vehicleModel->id,
                'title' => $vehicle->vehicleModel->title,
            ] : null,

            'vehicle_type' => $vehicle->vehicleType ? [
                'id' => $vehicle->vehicleType->id,
                'title' => $vehicle->vehicleType->title,
            ] : null,

            'city' => $vehicle->city ? [
                'id' => $vehicle->city->id,
                'name' => $vehicle->city->name,
            ] : null,

            'district' => $vehicle->district ? [
                'id' => $vehicle->district->id,
                'name' => $vehicle->district->name,
            ] : null,

            'media' => $vehicle->relationLoaded('media')
                ? $vehicle->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'original_url' => $media->getUrl(),
                        'collection_name' => $media->collection_name,
                        'custom_properties' => $media->custom_properties,
                    ];
                })->values()
                : [],
        ];
    }

    public function index(Request $request)
    {
        $tenant = $this->tenantOrFail();
        $filters = $this->validateFilters($request);
        $perPage = (int) ($filters['per_page'] ?? 12);

        $vehicleTypes = VehicleType::where('status', 1)
            ->select(['id', 'title'])
            ->orderBy('title')
            ->get()
            ->map(function ($type) {
                $type->slug = Str::slug($type->title);
                return $type;
            })
            ->values();

        $manufacturers = VehicleBrand::where('status', 1)
            ->select(['id', 'title'])
            ->orderBy('title')
            ->get()
            ->map(function ($brand) {
                $brand->slug = Str::slug($brand->title);
                return $brand;
            })
            ->values();

        $active = $this->resolveActiveSelections($filters);

        $models = VehicleModel::where('status', 1)
            ->select(['id', 'title', 'brand_id'])
            ->when($filters['brand_id'] ?? null, fn ($q, $brandId) => $q->where('brand_id', $brandId))
            ->orderBy('title')
            ->get()
            ->map(function ($model) {
                $model->slug = Str::slug($model->title);
                return $model;
            })
            ->values();

        $vehicles = $this->applyVehicleFilters(
            $this->baseVehicleQuery()->with([
                'manufacture:id,title',
                'vehicleModel:id,title,brand_id',
                'vehicleType:id,title',
                'city:id,name',
                'district:id,name',
                'media',
            ]),
            $filters
        )
            ->when(
                ($filters['sortBy'] ?? null) === 'oldest',
                fn ($q) => $q->orderBy('created_at', 'asc'),
                fn ($q) => $q->orderBy('created_at', 'desc')
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($vehicle) {
                return $this->mapVehicleCard($vehicle);
            });

        return Inertia::render($this->themePath() . '/Vehicles/Index', array_merge(
            [
                'tenant' => $tenant,
                'vehicles' => $vehicles,
                'vehicleTypes' => $vehicleTypes,
                'manufacturers' => $manufacturers,
                'models' => $models,
                'active' => $active,
                'filters' => $this->frontendFilters($filters),
                'requestQuery' => $this->frontendFilters($filters),
            ],
            $this->sharedFilterProps()
        ));
    }

    public function count(Request $request)
    {
        $filters = $this->validateFilters($request);

        return response()->json([
            'count' => $this->applyVehicleFilters($this->baseVehicleQuery(), $filters)->count(),
        ]);
    }

    public function product(Request $request, string $slug)
    {
        $vehicleBaseQuery = $this->baseVehicleQuery()->with([
            'media',
            'manufacture',
            'vehicleModel',
            'vehicleType',
            'district',
            'city',
            'customer',
            'customer.phoneNumbers',
            'vendorUser',
        ]);

        if ($request->filled('id')) {
            $vehicle = (clone $vehicleBaseQuery)
                ->where('id', $request->query('id'))
                ->firstOrFail();

            if ($vehicle->seo_url && $vehicle->seo_url !== $slug) {
                return redirect()->route('vendorsite.product', ['slug' => $vehicle->seo_url], 301);
            }
        } else {
            $vehicle = (clone $vehicleBaseQuery)
                ->where('seo_url', $slug)
                ->firstOrFail();
        }

        $vehicle->media->each(fn ($media) => $media->setAttribute('original_url', $media->getUrl()));

        $randomVehicles = $this->baseVehicleQuery()
            ->with(['manufacture', 'vehicleModel', 'vehicleType', 'city', 'district', 'media'])
            ->where('id', '!=', $vehicle->id)
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(function ($item) {
                $item->media->each(fn ($media) => $media->setAttribute('original_url', $media->getUrl()));

                return [
                    'id' => $item->id,
                    'seo_url' => $item->seo_url,
                    'year' => (int) ($item->year ?? 0),
                    'price' => $item->price ?? null,
                    'mileage' => (int) ($item->mileage ?? 0),
                    'condition' => $item->condition,
                    'transmission' => $item->transmission,
                    'fuel_type' => $item->fuel_type,
                    'engine_capacity' => $item->engine_capacity,
                    'availability' => $item->availability,

                    'manufacture' => $item->manufacture ? [
                        'title' => $item->manufacture->title,
                    ] : null,

                    'vehicle_model' => $item->vehicleModel ? [
                        'title' => $item->vehicleModel->title,
                    ] : null,

                    'vehicle_type' => $item->vehicleType ? [
                        'title' => $item->vehicleType->title,
                    ] : null,

                    'city' => $item->city ? [
                        'name' => $item->city->name,
                    ] : null,

                    'district' => $item->district ? [
                        'name' => $item->district->name,
                    ] : null,

                    'media' => $item->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'original_url' => $media->original_url,
                            'collection_name' => $media->collection_name,
                            'custom_properties' => $media->custom_properties,
                        ];
                    })->values(),
                ];
            })
            ->values();

        $seller = null;

        if ($vehicle->customer) {
            $seller = [
                'name' => $vehicle->customer->name,
                'status' => 'Seller',
                'phones' => collect($vehicle->customer->phoneNumbers ?? [])->map(function ($phone) {
                    return [
                        'phone' => $phone->phone,
                        'primary' => (bool) ($phone->primary ?? false),
                        'verified' => !empty($phone->verified_at),
                    ];
                })->values()->all(),
            ];

            if (empty($seller['phones']) && !empty($vehicle->customer->phone)) {
                $seller['phones'][] = [
                    'phone' => $vehicle->customer->phone,
                    'primary' => true,
                    'verified' => false,
                ];
            }
        } elseif ($vehicle->vendorUser) {
            $seller = [
                'name' => $vehicle->vendorUser->name ?? 'Seller',
                'status' => 'Seller',
                'phones' => !empty($vehicle->vendorUser->phone)
                    ? [[
                        'phone' => $vehicle->vendorUser->phone,
                        'primary' => true,
                        'verified' => false,
                    ]]
                    : [],
            ];
        } else {
            $seller = [
                'name' => 'Seller',
                'status' => 'Seller',
                'phones' => [],
            ];
        }

        return Inertia::render($this->themePath() . '/Product_view/index', [
            'vehicle' => $vehicle,
            'seller' => $seller,
            'randomVehicles' => $randomVehicles,
        ]);
    }
}