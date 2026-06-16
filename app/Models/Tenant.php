<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tenant extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'source_requested_vendor_id',
        'theme_id',
        'status',
        'legal_business_name',
        'store_display_name',
        'vendor_subscription_plan_id',
        'vendor_subscription_status',
        'vendor_panel_enabled',
        'vendor_subscription_started_at',
        'vendor_subscription_ends_at',
        'vendor_trial_ends_at',
        'address',
        'country',
        'country_code',
        'search_location',
        'google_place_id',
        'postal_code',
        'address_line_1',
        'address_line_2',
        'state_province',
        'city',
        'contact',
        'business_email',
        'owner_name',
        'business_registration_number',
        'website_order_types',
        'food_types',
        'opening_from',
        'opening_to',
        'business_logo_path',
        'business_photo_paths',
        'restaurant_page_image_paths',
        'business_license_path',
    ];

    protected $casts = [
        'website_order_types' => 'array',
        'food_types' => 'array',
        'business_photo_paths' => 'array',
        'restaurant_page_image_paths' => 'array',
        'vendor_panel_enabled' => 'boolean',
        'vendor_subscription_started_at' => 'datetime',
        'vendor_subscription_ends_at' => 'datetime',
        'vendor_trial_ends_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('VendorLogo')->singleFile();
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function vendorSubscriptionPlan()
    {
        return $this->belongsTo(VendorSubscriptionPlan::class, 'vendor_subscription_plan_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
