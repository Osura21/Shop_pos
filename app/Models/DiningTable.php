<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class DiningTable extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const SHAPES = [
        'square' => 'Square',
        'rectangle' => 'Rectangle',
        'round' => 'Round',
    ];

    public const STATUSES = [
        'available' => 'Available',
        'occupied' => 'Occupied',
    ];

    protected $table = 'dining_tables';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'floor_id',
        'zone_id',
        'name',
        'shape',
        'capacity',
        'status',
        'qr_token',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($table) {
            if (!$table->qr_token) {
                $table->qr_token = Str::upper(Str::random(12));
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function mergeItems()
    {
        return $this->hasMany(TableMergeItem::class, 'dining_table_id');
    }
}
