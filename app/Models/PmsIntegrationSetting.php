<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PmsIntegrationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'property_id',
        'pms_base_url',
        'pms_api_key',
        'active',
    ];

    protected $hidden = [
        'pms_api_key',
    ];

    protected $casts = [
        'pms_api_key' => 'encrypted',
        'active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Tenant::class, 'vendor_id');
    }
}
