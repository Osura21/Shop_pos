<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCardTransaction extends Model
{
    protected $fillable = [
        'uuid',
        'tenant_id',
        'branch_id',
        'gift_card_id',
        'pos_transaction_id',
        'pos_transaction_payment_id',
        'created_by',
        'currency_mode',
        'currency_code',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'balance_before' => 'decimal:3',
        'balance_after' => 'decimal:3',
        'occurred_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(GiftCard::class, 'gift_card_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function posTransaction()
    {
        return $this->belongsTo(PosTransaction::class);
    }

    public function payment()
    {
        return $this->belongsTo(PosTransactionPayment::class, 'pos_transaction_payment_id');
    }
}
