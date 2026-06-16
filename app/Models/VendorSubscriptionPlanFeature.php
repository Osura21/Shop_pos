<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSubscriptionPlanFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_subscription_plan_id',
        'feature_key',
        'feature_name',
        'feature_group',
        'value_type',
        'enabled',
        'is_unlimited',
        'limit_value',
        'unit',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_unlimited' => 'boolean',
        'limit_value' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(VendorSubscriptionPlan::class, 'vendor_subscription_plan_id');
    }
}
