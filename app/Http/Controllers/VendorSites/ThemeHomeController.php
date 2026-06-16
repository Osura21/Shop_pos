<?php

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

class ThemeHomeController extends Controller
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

    protected function conditions(): array
    {
        return [
            ['slug' => 'brand-new', 'name' => 'Brand New'],
            ['slug' => 'used', 'name' => 'Used'],
            ['slug' => 'reconditioned', 'name' => 'Reconditioned'],
        ];
    }

    protected function baseVehicleQuery()
    {
        $tenant = $this->tenantOrFail();

        return Vehicle::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 1);
    }

    protected function listingVehicleQuery()
    {
        return $this->baseVehicleQuery()->with([
            'manufacture:id,title',
            'vehicleModel:id,title',
            'vehicleType:id,title',
            'city:id,name',
            'district:id,name',
            'media',
        ]);
    }

    protected function mapVehicleCard(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'slug' => $vehicle->seo_url,
            'image' => $vehicle->image_url,
            'year' => (int) ($vehicle->year ?? 0),
            'make' => $vehicle->manufacture?->title,
            'model' => $vehicle->vehicleModel?->title,
            'type' => $vehicle->vehicleType?->title,
            'mileage' => (int) ($vehicle->mileage ?? 0),
            'fuel' => $vehicle->FuelTypeString,
            'featured' => (bool) $vehicle->featured,
            'price' => $vehicle->price ?? null,
            'city' => $vehicle->city?->name,
            'district' => $vehicle->district?->name,
            'condition' => $vehicle->condition,
            'transmission' => $vehicle->transmission,
        ];
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

    
        return [
            'districts' => $districts->values(),
            'citiesAll' => $citiesAll->values(),
            'conditions' => $this->conditions(),
            'currentYear' => (int) date('Y'),
        ];
    }

    public function home()
    {
        $tenant = $this->tenantOrFail();

        return Inertia::render($this->themePath() . '/Home/Home', array_merge(
            [
                'tenant' => $tenant,
                'filters' => [
                    'district_id' => null,
                    'city_id' => null,
                    'type_id' => null,
                    'brand_id' => null,
                    'model_id' => null,
                    'condition' => null,
                    'condition_slug' => null,
                    'year_from' => null,
                    'year_to' => null,
                    'min_price' => null,
                    'max_price' => null,
                ],
            ],
            $this->sharedFilterProps()
        ));
    }

    public function brands()
    {
        $tenantId = $this->tenantOrFail()->id;

        return response()->json(
            VehicleBrand::query()
                ->where('status', 1)
                ->whereIn('id', function ($query) use ($tenantId) {
                    $query->select('manufacture_id')
                        ->from('vehicles')
                        ->where('tenant_id', $tenantId)
                        ->where('status', 1)
                        ->whereNotNull('manufacture_id');
                })
                ->orderBy('title')
                ->get(['id', 'title'])
        );
    }

    public function models(Request $request)
    {
        $tenantId = $this->tenantOrFail()->id;

        $data = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:vehicle_brands,id'],
        ]);

        return response()->json(
            VehicleModel::query()
                ->where('status', 1)
                ->where('brand_id', $data['brand_id'])
                ->whereIn('id', function ($query) use ($tenantId, $data) {
                    $query->select('vehicle_model_id')
                        ->from('vehicles')
                        ->where('tenant_id', $tenantId)
                        ->where('status', 1)
                        ->where('manufacture_id', $data['brand_id'])
                        ->whereNotNull('vehicle_model_id');
                })
                ->orderBy('title')
                ->get(['id', 'brand_id', 'title'])
        );
    }

    public function types()
    {
        $tenantId = $this->tenantOrFail()->id;

        return response()->json(
            VehicleType::query()
                ->where('status', 1)
                ->whereIn('id', function ($query) use ($tenantId) {
                    $query->select('vehicle_type_id')
                        ->from('vehicles')
                        ->where('tenant_id', $tenantId)
                        ->where('status', 1)
                        ->whereNotNull('vehicle_type_id');
                })
                ->orderBy('title')
                ->get(['id', 'title'])
                ->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'title' => $type->title,
                        'image_url' => data_get($type, 'image_url') ?: data_get($type, 'image'),
                    ];
                })
                ->values()
        );
    }

    public function featuredVehicles(Request $request)
    {
        $limit = max(1, min(12, (int) $request->input('limit', 6)));

        $vehicles = $this->listingVehicleQuery()
            ->where('featured', 1)
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn ($vehicle) => $this->mapVehicleCard($vehicle))
            ->values();

        return response()->json($vehicles);
    }

    public function brandNewVehicles(Request $request)
    {
        $limit = max(1, min(12, (int) $request->input('limit', 4)));

        $vehicles = $this->listingVehicleQuery()
            ->where('condition', 'Brand New')
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn ($vehicle) => $this->mapVehicleCard($vehicle))
            ->values();

        return response()->json($vehicles);
    }

    public function usedVehicles(Request $request)
    {
        $limit = max(1, min(12, (int) $request->input('limit', 4)));

        $vehicles = $this->listingVehicleQuery()
            ->where('condition', 'Used')
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn ($vehicle) => $this->mapVehicleCard($vehicle))
            ->values();

        return response()->json($vehicles);
    }
}