<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCashMovement extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'branch_id',
        'pos_register_id',
        'pos_session_id',
        'user_id',
        'direction',
        'reason',
        'amount',
        'balance_before',
        'balance_after',
        'reference',
        'notes',
        'occurred_at',
        'currency_mode',
'currency_code',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'balance_before' => 'decimal:3',
        'balance_after' => 'decimal:3',
        'occurred_at' => 'datetime',
    ];

    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function session()
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}
}
