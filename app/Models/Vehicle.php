<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\User;

class Vehicle extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $appends = [
        'FuelTypeString',
        'TransmissionString',
        'AvailabilityString',
        'image_url',
    ];
    protected $casts = [
        'negotiable' => 'boolean',
        'price' => 'decimal:2',
    ];

    protected $fillable = [
        'vehicle_type_id',
        'manufacture_id',
        'vehicle_model_id',
        'seo_url',

        'transmission',
        'year',
        'chassis_no',
        'condition',
        'seats',
        'doors',
        'passengers',
        'engine_capacity',
        'mileage',
        'fuel_type',
        'drive_type',
        'auction_grade',
        'interior_condition',
        'availability',
        'editorContent',
        'featured',
        'latest',
        'status',
        'district_id',
        'city_id',

        'tenant_id',
        'price',
        'customer_id',
        'negotiable',

    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function manufacture()
    {
        return $this->belongsTo(VehicleBrand::class, 'manufacture_id');
    }

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function getFeaturesListAttribute()
    {
        return json_decode($this->features ?? '[]', true) ?: [];
    }

    public function getFuelTypeStringAttribute($type)
    {
        return match ((int)$type) {
            0 => 'Diesel',
            1 => 'Petrol',
            2 => 'Hybrid',
            3 => 'Electric',
            default => '',
        };
    }

    public function getTransmissionStringAttribute($value)
    {
        return match ((int)$value) {
            0 => 'Auto',
            1 => 'Manual',
            2 => 'Triptonic',
            default => '',
        };
    }

    public function getAvailabilityStringAttribute($value)
    {
        return match ((int)$value) {
            0 => 'Available',
            1 => 'Arriving',
            2 => 'Sold',
            default => '',
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('vehicle_main')->singleFile();

        $this->addMediaCollection('vehicle_gallery');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('vehicle_main') ?: null;
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }


    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function vendorUser()
    {
        return $this->belongsTo(User::class, 'tenant_id', 'tenant_id');
    }
}
