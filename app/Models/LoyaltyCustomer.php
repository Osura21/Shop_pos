<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'loyalty_program_id',
        'loyalty_tier_id',
        'points_balance',
        'lifetime_points',
        'lifetime_spend',
        'secondary_lifetime_spend',
        'last_earned_at',
        'last_redeemed_at',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'lifetime_points' => 'integer',
        'lifetime_spend' => 'decimal:3',
        'secondary_lifetime_spend' => 'decimal:3',
        'last_earned_at' => 'datetime',
        'last_redeemed_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function program()
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function tier()
    {
        return $this->belongsTo(LoyaltyTier::class, 'loyalty_tier_id');
    }
}
