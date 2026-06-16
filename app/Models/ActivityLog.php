<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_type',
        'user_name',
        'user_email',
        'user_role',
        'log_name',
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'old_values',
        'new_values',
        'properties',
        'ip_address',
        'http_method',
        'url',
        'user_agent',
        'is_desktop',
        'platform',
        'browser',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'properties' => 'array',
        'is_desktop' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
