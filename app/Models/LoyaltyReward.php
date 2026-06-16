<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LoyaltyReward extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'loyalty_tier_id',
        'name',
        'description',
        'type',
        'points_cost',
        'icon_path',
        'value_type',
        'value',
        'secondary_value',
        'product_id',
        'quantity',
        'target_tier_id',
        'code_prefix',
        'expires_in_days',
        'minimum_order_total',
        'secondary_minimum_order_total',
        'maximum_order_total',
        'secondary_maximum_order_total',
        'minimum_spend',
        'secondary_minimum_spend',
        'branch_ids',
        'available_days',
        'starts_at',
        'ends_at',
        'max_redemptions_per_order',
        'usage_limit',
        'per_customer_limit',
        'redeemed_count',
        'customers_count',
        'is_active',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'value' => 'decimal:3',
        'secondary_value' => 'decimal:3',
        'quantity' => 'decimal:3',
        'minimum_order_total' => 'decimal:3',
        'secondary_minimum_order_total' => 'decimal:3',
        'maximum_order_total' => 'decimal:3',
        'secondary_maximum_order_total' => 'decimal:3',
        'minimum_spend' => 'decimal:3',
        'secondary_minimum_spend' => 'decimal:3',
        'branch_ids' => 'array',
        'available_days' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'icon_url',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('Loyalty_Reward_Images')
            ->singleFile();
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('Loyalty_Reward_Images') ?: $this->icon_path;
    }

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function tier()
    {
        return $this->belongsTo(LoyaltyTier::class, 'loyalty_tier_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function targetTier()
    {
        return $this->belongsTo(LoyaltyTier::class, 'target_tier_id');
    }
}