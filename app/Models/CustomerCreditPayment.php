<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCreditPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'customer_id',
        'branch_id',
        'pos_register_id',
        'pos_session_id',
        'user_id',
        'currency_code',
        'amount',
        'payment_method',
        'receipt_no',
        'reference',
        'notes',
        'received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'received_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function session()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }
}
