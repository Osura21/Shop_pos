<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransactionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_transaction_id',
        'payment_method',
        'amount',
        'transaction_id',
        'gift_card_id',
        'gift_card_code',
        'meta',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'meta' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class);
    }
}
