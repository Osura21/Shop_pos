<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthenticationLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_type',
        'user_name',
        'user_email',
        'user_role',
        'ip_address',
        'user_agent',
        'is_desktop',
        'platform',
        'browser',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'is_desktop' => 'boolean',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
