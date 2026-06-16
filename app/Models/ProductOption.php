<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'template_key',
        'name',
        'type',
        'is_required',
        'base_price',
        'secondary_price',
        'price_type',
        'sort_order',
        'is_collapsed',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_collapsed' => 'boolean',
        'base_price' => 'decimal:3',
        'secondary_price' => 'decimal:3',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function rows()
    {
        return $this->hasMany(ProductOptionRow::class)->orderBy('sort_order');
    }
}