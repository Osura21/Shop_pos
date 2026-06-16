<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosSessionItemOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'pos_session_item_id',
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
        return $this->belongsTo(PosSessionItem::class, 'pos_session_item_id');
    }
}