<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'ingredient_id',
        'quantity',
        'received_quantity',

        // base
        'unit_cost',
        'line_total',

        // secondary
        'secondary_unit_cost',
        'secondary_line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',

        'unit_cost' => 'decimal:3',
        'line_total' => 'decimal:3',

        'secondary_unit_cost' => 'decimal:3',
        'secondary_line_total' => 'decimal:3',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_quantity);
    }
}