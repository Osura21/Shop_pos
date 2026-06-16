<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ingredient extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_ids',
        'unit_id',
        'name',
        'current_stock',
        'alert_quantity',
        'cost_per_unit',
         'secondary_cost_per_unit',
        'is_active',
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'alert_quantity' => 'decimal:3',
        'cost_per_unit' => 'decimal:3',
        'secondary_cost_per_unit' => 'decimal:3',
        'is_active' => 'boolean',
        'branch_ids' => 'array',
    ];

    public function branches()
    {
        return Branch::whereIn('id', $this->branch_ids ?? [])->get();
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
