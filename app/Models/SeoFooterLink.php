<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoFooterLink extends Model
{
    protected $fillable = [
        'country',
        'country_code',
        'location',
        'link_text',
        'food_type',
        'food_type_slug',
        'order_type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
