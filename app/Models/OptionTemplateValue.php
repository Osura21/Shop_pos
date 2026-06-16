<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OptionTemplateValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'option_template_id',
        'label',
        'base_price',
        'secondary_price',
        'price_type',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:3',
        'secondary_price' => 'decimal:3',
    ];

    public function optionTemplate()
    {
        return $this->belongsTo(OptionTemplate::class);
    }
}