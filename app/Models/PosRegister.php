<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosRegister extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'branch_id',
        'name',
        'code',
        'invoice_printer',
        'bill_printer',
        'note',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sessions()
    {
        return $this->hasMany(PosSession::class, 'pos_register_id');
    }

    public function cashMovements()
    {
        return $this->hasMany(PosCashMovement::class, 'pos_register_id');
    }

    public function activeSession()
    {
        return $this->hasOne(PosSession::class, 'pos_register_id')
            ->where('status', 'open')
            ->latestOfMany('opened_at');
    }
    public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updater()
{
    return $this->belongsTo(User::class, 'updated_by');
}
}
