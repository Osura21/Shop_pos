<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosKitchenTicketItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_kitchen_ticket_id',
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
        'status',
        'started_at',
        'ready_at',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:3',
        'option_total' => 'decimal:3',
        'line_subtotal' => 'decimal:3',
        'tax_total' => 'decimal:3',
        'line_total' => 'decimal:3',
        'tax_snapshot' => 'array',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(PosKitchenTicket::class, 'pos_kitchen_ticket_id');
    }

    public function options()
    {
        return $this->hasMany(PosKitchenTicketItemOption::class);
    }
}
