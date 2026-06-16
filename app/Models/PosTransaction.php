<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'branch_id',
        'customer_id',
        'pos_register_id',
        'pos_session_id',
        'user_id',
        'currency_mode',
        'currency_code',
        'payment_mode',
        'total_products',
        'subtotal',
        'discount_total',
        'promotion_discount_id',
        'promotion_voucher_id',
        'promotion_code',
        'loyalty_reward_id',
        'loyalty_points_redeemed',
        'loyalty_discount_total',
        'loyalty_points_earned',
        'tax_total',
        'grand_total',
        'total_paid',
        'due_amount',
        'customer_given_amount',
        'change_return',
        'print_bill',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:3',
        'discount_total' => 'decimal:3',
        'loyalty_points_redeemed' => 'integer',
        'loyalty_discount_total' => 'decimal:3',
        'loyalty_points_earned' => 'integer',
        'tax_total' => 'decimal:3',
        'grand_total' => 'decimal:3',
        'total_paid' => 'decimal:3',
        'due_amount' => 'decimal:3',
        'customer_given_amount' => 'decimal:3',
        'change_return' => 'decimal:3',
        'print_bill' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function payments()
    {
        return $this->hasMany(PosTransactionPayment::class);
    }

    public function session()
{
    return $this->belongsTo(PosSession::class, 'pos_session_id');
}

public function ticket()
{
    return $this->belongsTo(PosKitchenTicket::class, 'pos_kitchen_ticket_id');
}
    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
