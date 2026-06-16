<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'branch_id',
        'promotion_discount_id',
        'promotion_voucher_id',
        'pos_session_id',
        'pos_transaction_id',
        'customer_id',
        'created_by',
        'promotion_type',
        'promotion_code',
        'currency_mode',
        'currency_code',
        'subtotal',
        'discount_amount',
        'redeemed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'redeemed_at' => 'datetime',
    ];
}
