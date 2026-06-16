<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPhoneNumber extends Model
{
    protected $fillable = ['customer_id', 'phone', 'verified_at'];

    protected $casts = [
        'verified_at' => 'datetime',
    ];
}