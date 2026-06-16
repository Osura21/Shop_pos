<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseReceiptItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_receipt_id',
        'ingredient_id',
        'quantity',
        'unit_name',
        'unit_symbol',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function receipt()
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}