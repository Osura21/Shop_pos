<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tax extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPES = [
        'exclusive' => 'Exclusive',
        'inclusive' => 'Inclusive',
    ];

    public const ORDER_TYPES = [
        'takeaway' => 'Takeaway',
        'dine_in' => 'Dine-In',
        'pick_up' => 'Pick-up',
        'drive_thru' => 'Drive-Thru',
        'pre_order' => 'Pre-Order',
        'catering' => 'Catering',
    ];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'name',
        'code',
        'rate',
        'type',
        'is_compound',
        'is_global',
        'is_active',
        'order_types',
    ];

    protected $casts = [
        'rate' => 'decimal:3',
        'is_compound' => 'boolean',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'order_types' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tax');
    }
}
