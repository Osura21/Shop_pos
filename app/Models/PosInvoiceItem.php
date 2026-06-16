<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_invoice_id',
        'product_name',
        'status',
        'unit_price',
        'qty',
        'subtotal',
        'tax_total',
        'line_total',
        'cost_price',
        'revenue',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
        'unit_price' => 'decimal:3',
        'qty' => 'decimal:3',
        'subtotal' => 'decimal:3',
        'tax_total' => 'decimal:3',
        'line_total' => 'decimal:3',
        'cost_price' => 'decimal:3',
        'revenue' => 'decimal:3',
    ];

    public function invoice()
    {
        return $this->belongsTo(PosInvoice::class, 'pos_invoice_id');
    }
}