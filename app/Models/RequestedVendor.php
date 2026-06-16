<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestedVendor extends Model
{
    protected $table = 'requested_vendor';

    protected $fillable = [
        'name',
        'admin_name',
        'slug',
        'status',
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
        'reason',
    ];

    protected $casts = [
        'food_types' => 'array',
        'service_options' => 'array',
        'terms_accepted' => 'boolean',
        'business_photo_paths' => 'array',
        'restaurant_page_image_paths' => 'array',
    ];
}
