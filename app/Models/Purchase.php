<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'supplier_id',
        'reference_no',
        'status',
        'expected_at',
        'notes',

        // legacy / fallback
        'currency',

        // base / secondary currency codes
        'base_currency_code',
        'secondary_currency_code',

        // tax / discount config
        'tax_type',
        'tax_value',
        'secondary_tax_value',
        'discount_type',
        'discount_value',
        'secondary_discount_value',

        // base totals
        'subtotal',
        'tax',
        'discount',
        'total',

        // secondary totals
        'secondary_subtotal',
        'secondary_tax',
        'secondary_discount',
        'secondary_total',
    ];

    protected $casts = [
        'expected_at' => 'date',

        'tax_value' => 'decimal:3',
        'secondary_tax_value' => 'decimal:3',
        'discount_value' => 'decimal:3',
        'secondary_discount_value' => 'decimal:3',

        'subtotal' => 'decimal:3',
        'tax' => 'decimal:3',
        'discount' => 'decimal:3',
        'total' => 'decimal:3',

        'secondary_subtotal' => 'decimal:3',
        'secondary_tax' => 'decimal:3',
        'secondary_discount' => 'decimal:3',
        'secondary_total' => 'decimal:3',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(PurchaseReceipt::class)->latest();
    }
}
