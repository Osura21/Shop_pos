<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_name',
        'subscription_plan_code',
        'is_default',
        'short_description',
        'status',
        'price',
        'billing_interval',
        'discount_allowed',
        'discount_type',
        'discount_value',
        'auto_renew',
        'cancellation_policy',
        'refund_policy',
        'display_order',
        'badge',
        'highlight_plan',
        'plan_card_color',
        'plan_icon',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'discount_allowed' => 'boolean',
        'auto_renew' => 'boolean',
        'highlight_plan' => 'boolean',
        'price' => 'decimal:2',
        'discount_value' => 'decimal:2',
    ];

    public function planFeatures(): HasMany
    {
        return $this->hasMany(SubscriptionPlanFeature::class, 'subscription_plan_id');
    }
}