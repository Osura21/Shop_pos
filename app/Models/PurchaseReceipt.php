<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'receipt_no',
        'reference_no',
        'received_at',
        'received_by_name',
        'note',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReceiptItem::class)->latest();
    }
}