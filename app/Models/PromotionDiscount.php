<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'description',
        'type',
        'value',
        'secondary_value',
        'max_discount',
        'secondary_max_discount',
        'min_spend',
        'secondary_min_spend',
        'max_spend',
        'secondary_max_spend',
        'starts_at',
        'ends_at',
        'usage_limit',
        'per_customer_limit',
        'used_count',
        'order_types',
        'available_days',
        'category_ids',
        'product_ids',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:3',
        'secondary_value' => 'decimal:3',
        'max_discount' => 'decimal:3',
        'secondary_max_discount' => 'decimal:3',
        'min_spend' => 'decimal:3',
        'secondary_min_spend' => 'decimal:3',
        'max_spend' => 'decimal:3',
        'secondary_max_spend' => 'decimal:3',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'usage_limit' => 'integer',
        'per_customer_limit' => 'integer',
        'used_count' => 'integer',
        'order_types' => 'array',
        'available_days' => 'array',
        'category_ids' => 'array',
        'product_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
