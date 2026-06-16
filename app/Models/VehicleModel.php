<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'title',
        'status',
    ];

    public function manufacture()
    {
        return $this->belongsTo(VehicleBrand::class, 'brand_id');
    }
}
