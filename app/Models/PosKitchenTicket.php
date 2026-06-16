<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosKitchenTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'pos_session_id',
        'pos_register_id',
        'branch_id',
        'customer_id',
        'dining_table_id',
        'channel',
        'waiter_name',
        'customer_name',
        'car_plate',
        'car_description',
        'scheduled_at',
        'notes',
        'guest_count',
        'pms_guest_snapshot',
        'currency_mode',
        'currency_code',
        'subtotal',
        'tax_total',
        'discount_total',
        'grand_total',
        'status',
        'sent_to_kitchen_at',
        'prepared_at',
        'ready_at',
        'served_at',
        'payment_status',
        'payment_status',
        'cancel_reason',
        'cancel_note',
        'cancelled_at',
        'pms_posting_status',
        'pms_posted_at',
        'pms_response',
        'pms_booking_id',
        'pms_room_key_id',
        'paid_amount',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'pms_guest_snapshot' => 'array',
        'sent_to_kitchen_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'prepared_at' => 'datetime',
        'ready_at' => 'datetime',
        'served_at' => 'datetime',
        'subtotal' => 'decimal:3',
        'tax_total' => 'decimal:3',
        'discount_total' => 'decimal:3',
        'grand_total' => 'decimal:3',
        'pms_response' => 'array',
        'pms_posted_at' => 'datetime',
        'paid_amount' => 'decimal:3',
    ];

    public function items()
    {
        return $this->hasMany(PosKitchenTicketItem::class);
    }

    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function table()
    {
        return $this->belongsTo(DiningTable::class, 'dining_table_id');
    }
    public function customer()
{
    return $this->belongsTo(Customer::class, 'customer_id');
}
}
