<?php

namespace App\MediaLibrary;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
class VehiclePathGenerator implements PathGenerator
{
    protected function ownerSlug(Media $media): string
    {
        $owner = $media->getCustomProperty('owner_slug');
        if (is_string($owner) && trim($owner) !== '') {
            return Str::slug($owner);
        }

        $customerId = $media->getCustomProperty('customer_id') ?: null;
        if ($customerId) return 'customer-' . $customerId;

        $tenantId = $media->getCustomProperty('tenant_id') ?: null;
        if ($tenantId) return 'tenant-' . $tenantId;

        return 'unknown-owner';
    }

    protected function vehicleSlug(Media $media): string
    {
        $slug = $media->getCustomProperty('vehicle_slug');
        if (is_string($slug) && trim($slug) !== '') {
            return Str::slug($slug);
        }

        return $media->model_id ? ('vehicle-' . $media->model_id) : 'vehicle';
    }

    protected function collectionSlug(Media $media): string
    {
        return Str::slug($media->collection_name ?: 'media'); // keep your current folder style
    }

    public function getPath(Media $media): string
    {
        return "vehicle/{$this->ownerSlug($media)}/{$this->vehicleSlug($media)}/{$this->collectionSlug($media)}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive-images/';
    }
}