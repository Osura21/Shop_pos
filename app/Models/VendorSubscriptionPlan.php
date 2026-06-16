<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VendorSubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plan_name',
        'plan_code',
        'short_description',
        'status',
        'is_default',
        'monthly_price',
        'yearly_price',
        'yearly_discount_type',
        'yearly_discount_value',
        'currency_code',
        'trial_days',
        'auto_renew',
        'cancellation_policy',
        'refund_policy',
        'display_order',
        'badge',
        'highlight_plan',
        'most_popular',
        'plan_card_color',
        'icon_key',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'yearly_discount_value' => 'decimal:2',
        'trial_days' => 'integer',
        'auto_renew' => 'boolean',
        'display_order' => 'integer',
        'highlight_plan' => 'boolean',
        'most_popular' => 'boolean',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(VendorSubscriptionPlanFeature::class)
            ->orderBy('sort_order')
            ->orderBy('feature_name');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Tenant::class, 'vendor_subscription_plan_id');
    }
}
