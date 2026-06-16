<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftCardBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'base_currency_code',
        'secondary_currency_code',
        'name',
        'prefix',
        'quantity',
        'value',
        'secondary_value',
        'cards_generated',
        'cards_used',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'value' => 'decimal:3',
        'secondary_value' => 'decimal:3',
        'cards_generated' => 'integer',
        'cards_used' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function cards()
    {
        return $this->hasMany(GiftCard::class);
    }
}
