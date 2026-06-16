<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VehicleBrand extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $table = 'vehicle_brands';

    protected $fillable = [
        'title',
        'status',
        'featured',
    ];

    protected $casts = [
        'status' => 'boolean',
        'featured' => 'boolean',
    ];

    // Optional helper for Vue / DataTables
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('vehicle_brand_image');
        return $media ? $media->getUrl() : null;
    }
}
