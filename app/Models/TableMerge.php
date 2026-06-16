<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class TableMerge extends Model
{
    use HasFactory;

    public const TYPES = [
        'billing' => 'Billing',
        'capacity' => 'Capacity',
    ];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'merge_code',
        'type',
        'created_by_name',
        'closed_by_name',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($merge) {
            if (!$merge->merge_code) {
                $merge->merge_code = 'TM-' . Str::upper(Str::random(8));
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function items()
    {
        return $this->hasMany(TableMergeItem::class);
    }
}