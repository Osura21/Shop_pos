<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class PosSessionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_session_id',
        'product_id',
        'product_name',
        'image_url',
        'qty',
        'unit_price',
        'option_total',
        'line_subtotal',
        'tax_total',
        'line_total',
        'tax_snapshot',
        'notes',
        'currency_mode',
        'currency_code',
        'loyalty_gift_id',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'option_total' => 'decimal:3',
        'line_subtotal' => 'decimal:3',
        'tax_total' => 'decimal:3',
        'line_total' => 'decimal:3',
        'tax_snapshot' => 'array',
        'loyalty_gift_id' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function options()
    {
        return $this->hasMany(PosSessionItemOption::class, 'pos_session_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function loyaltyGift()
    {
        return $this->belongsTo(LoyaltyGift::class, 'loyalty_gift_id');
    }
}