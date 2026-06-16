<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'pos_kitchen_ticket_id',
        'pos_transaction_id',
        'branch_id',
        'customer_id',
        'invoice_no',
        'seller_name',
        'buyer_name',
        'type',
        'status',
        'purpose',
        'kind',
        'currency_code',
        'currency_rate',
        'cost_price',
        'revenue',
        'subtotal',
        'tax_total',
        'discount_total',
        'discount_mode',
        'discount_type',
        'discount_value',
        'promotion_discount_id',
        'promotion_voucher_id',
        'promotion_code',
        'loyalty_reward_id',
        'loyalty_points_redeemed',
        'loyalty_discount_total',
        'loyalty_points_earned',
        'total',
        'paid_amount',
        'refunded_amount',
        'net_paid',
        'payments',
        'notes',
        'pms_posting_status',
        'pms_posted_at',
        'pms_response',
        'pms_booking_id',
        'pms_room_key_id',
        'pms_room_charge_amount',
        'pms_guest_snapshot',
        'issued_at',
    ];

    protected $casts = [
        'payments' => 'array',
        'pms_response' => 'array',
        'pms_guest_snapshot' => 'array',
        'pms_posted_at' => 'datetime',
        'pms_room_charge_amount' => 'decimal:3',
        'issued_at' => 'datetime',
        'currency_rate' => 'decimal:4',
        'cost_price' => 'decimal:3',
        'revenue' => 'decimal:3',
        'subtotal' => 'decimal:3',
        'tax_total' => 'decimal:3',
        'discount_total' => 'decimal:3',
        'discount_value' => 'decimal:3',
        'loyalty_points_redeemed' => 'integer',
        'loyalty_discount_total' => 'decimal:3',
        'loyalty_points_earned' => 'integer',
        'total' => 'decimal:3',
        'paid_amount' => 'decimal:3',
        'refunded_amount' => 'decimal:3',
        'net_paid' => 'decimal:3',
    ];

    public function items()
    {
        return $this->hasMany(PosInvoiceItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function promotionDiscount()
    {
        return $this->belongsTo(PromotionDiscount::class, 'promotion_discount_id');
    }

    public function promotionVoucher()
    {
        return $this->belongsTo(PromotionVoucher::class, 'promotion_voucher_id');
    }
}
