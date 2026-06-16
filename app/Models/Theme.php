<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = ['name','slug','path','is_active'];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}

