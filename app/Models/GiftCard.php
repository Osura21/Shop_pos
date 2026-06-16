<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'customer_id',
        'gift_card_batch_id',
        'base_currency_code',
        'secondary_currency_code',
        'code',
        'initial_balance',
        'current_balance',
        'secondary_initial_balance',
        'secondary_current_balance',
        'status',
        'expires_at',
        'notes',
        'purchased_at',
        'used_at',
        'disabled_at',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:3',
        'current_balance' => 'decimal:3',
        'secondary_initial_balance' => 'decimal:3',
        'secondary_current_balance' => 'decimal:3',
        'expires_at' => 'date',
        'purchased_at' => 'datetime',
        'used_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function batch()
    {
        return $this->belongsTo(GiftCardBatch::class, 'gift_card_batch_id');
    }

    public function transactions()
    {
        return $this->hasMany(GiftCardTransaction::class);
    }
}
