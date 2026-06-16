<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'earning_rate',
        'points_expire_after_days',
        'is_active',
    ];

    protected $casts = [
        'earning_rate' => 'decimal:6',
        'points_expire_after_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tiers()
    {
        return $this->hasMany(LoyaltyTier::class);
    }

    public function rewards()
    {
        return $this->hasMany(LoyaltyReward::class);
    }
}
