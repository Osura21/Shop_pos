<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyPromotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'name',
        'description',
        'type',
        'multiplier',
        'bonus_points',
        'minimum_spend',
        'secondary_minimum_spend',
        'branch_ids',
        'available_days',
        'starts_at',
        'ends_at',
        'usage_limit',
        'per_customer_limit',
        'used_count',
        'customers_count',
        'is_active',
    ];

    protected $casts = [
        'multiplier' => 'decimal:3',
        'bonus_points' => 'integer',
        'minimum_spend' => 'decimal:3',
        'secondary_minimum_spend' => 'decimal:3',
        'branch_ids' => 'array',
        'available_days' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }
}
