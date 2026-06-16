<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosKitchenTicketItemOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_kitchen_ticket_item_id',
        'option_name',
        'option_type',
        'value_label',
        'value_input',
        'price',
        'price_type',
    ];

    protected $casts = [
        'price' => 'decimal:3',
    ];

    public function item()
    {
        return $this->belongsTo(PosKitchenTicketItem::class, 'pos_kitchen_ticket_item_id');
    }
}