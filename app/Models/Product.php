<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'menu_id',
        'name',
        'sku',
        'brand',
        'unit_type',
        'is_loose_item',
        'description',
        'image_path',
        'base_price',
        'secondary_price',
        'cost_price',
        'current_stock',
        'reorder_level',
        'special_price_type',
        'base_special_price',
        'secondary_special_price',
        'special_price_start',
        'special_price_end',
        'new_from',
        'new_to',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:3',
        'secondary_price' => 'decimal:3',
        'cost_price' => 'decimal:3',
        'current_stock' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'base_special_price' => 'decimal:3',
        'secondary_special_price' => 'decimal:3',
        'special_price_start' => 'datetime',
        'special_price_end' => 'datetime',
        'new_from' => 'datetime',
        'new_to' => 'datetime',
        'is_loose_item' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'product_tax');
    }

    public function ingredients()
    {
        return $this->hasMany(ProductIngredient::class)->orderBy('sort_order');
    }

    public function options()
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }
    public function productOptions()
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function getActiveSpecialPrice(string $currencyMode = 'base')
    {
        $now = now();

        $hasSpecialPrice = $currencyMode === 'secondary'
            ? $this->secondary_special_price !== null
            : $this->base_special_price !== null;

        if (!$hasSpecialPrice) {
            return null;
        }

        if ($this->special_price_start && $now->lt($this->special_price_start)) {
            return null;
        }

        if ($this->special_price_end && $now->gt($this->special_price_end)) {
            return null;
        }

        $specialPrice = $currencyMode === 'secondary'
            ? (float) $this->secondary_special_price
            : (float) $this->base_special_price;

        if ($this->special_price_type === 'percentage') {
            $normalPrice = $currencyMode === 'secondary'
                ? (float) ($this->secondary_price ?? $this->base_price ?? 0)
                : (float) ($this->base_price ?? 0);
            return $normalPrice - ($normalPrice * $specialPrice / 100);
        }

        return $specialPrice;
    }
}
