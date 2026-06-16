<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'pos_register_id',
        'user_id',
        'opened_by',
        'closed_by',
        'branch_id',
        'menu_id',
        'editing_ticket_id',
        'customer_id',
        'dining_table_id',
        'channel',
        'waiter_name',
        'customer_name',
        'car_plate',
        'car_description',
        'scheduled_at',
        'notes',
        'closing_note',
        'guest_count',
        'pms_guest_snapshot',
        'discount_mode',
        'discount_type',
        'discount_value',
        'promotion_discount_id',
        'promotion_voucher_id',
        'promotion_code',
        'loyalty_reward_id',
        'loyalty_gift_id',
        'loyalty_points_redeemed',
        'loyalty_discount_total',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'status',
        'currency_mode',
        'currency_code',
        'opening_float',
        'current_balance',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'guest_count' => 'integer',
        'pms_guest_snapshot' => 'array',
        'scheduled_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'discount_value' => 'decimal:3',
        'loyalty_points_redeemed' => 'integer',
        'loyalty_discount_total' => 'decimal:3',
        'subtotal' => 'decimal:3',
        'tax_total' => 'decimal:3',
        'discount_total' => 'decimal:3',
        'grand_total' => 'decimal:3',
        'opening_float' => 'decimal:3',
        'current_balance' => 'decimal:3',
    ];

    public function items()
    {
        return $this->hasMany(PosSessionItem::class);
    }

    public function table()
    {
        return $this->belongsTo(DiningTable::class, 'dining_table_id');
    }

    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function cashMovements()
    {
        return $this->hasMany(PosCashMovement::class, 'pos_session_id');
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function branch()
{
    return $this->belongsTo(Branch::class, 'branch_id');
}

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function editingTicket()
    {
        return $this->belongsTo(PosKitchenTicket::class, 'editing_ticket_id');
    }

    public function loyaltyGift()
    {
        return $this->belongsTo(LoyaltyGift::class, 'loyalty_gift_id');
    }
}
