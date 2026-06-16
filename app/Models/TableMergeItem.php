<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TableMergeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_merge_id',
        'dining_table_id',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function merge()
    {
        return $this->belongsTo(TableMerge::class, 'table_merge_id');
    }

    public function table()
    {
        return $this->belongsTo(DiningTable::class, 'dining_table_id');
    }
}