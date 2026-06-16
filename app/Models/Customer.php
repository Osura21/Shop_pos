<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'name',
        'customer_type',
        'phone',
        'birthdate',
        'gender',
        'is_active',
        'username',
        'email',
        'note',
        'password',
        'registration_number',
        'vat_tin',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('Customer_Images');

        return $url ?: null;
    }

    public function loyaltyAccounts()
    {
        return $this->hasMany(LoyaltyCustomer::class);
    }
}
