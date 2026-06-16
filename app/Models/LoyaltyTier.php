<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LoyaltyTier extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'name',
        'benefits',
        'minimum_spend',
        'secondary_minimum_spend',
        'multiplier',
        'sort_order',
        'icon_path',
        'is_active',
    ];

    protected $casts = [
        'minimum_spend' => 'decimal:3',
        'secondary_minimum_spend' => 'decimal:3',
        'multiplier' => 'decimal:3',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'icon_url',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('Loyalty_Tier_Images')
            ->singleFile();
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('Loyalty_Tier_Images') ?: $this->icon_path;
    }

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }
}