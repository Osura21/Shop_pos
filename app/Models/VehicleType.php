<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VehicleType extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'vehicle_types';

    protected $fillable = [
        'title',
        'status',
        'featured',
    ];

    protected $casts = [
        'status' => 'boolean',
        'featured' => 'boolean',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('vehicle_type_image');
        return $media ? $media->getUrl() : null;
    }
}
