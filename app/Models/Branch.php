<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use LogsActivity;

    protected $fillable = [
        'tenant_id',
        'name',
        'legal_name',
        'phone',
        'email',
        'is_active',
        'registration_number',
        'vat_tin',
        'currency',
        'timezone',
        'country',
        'postal_code',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'latitude',
        'longitude',
        'order_types',
        'payment_methods',
        'cash_difference_threshold',
        'quick_pay_amount_1',
        'quick_pay_amount_2',
        'quick_pay_amount_3',
        'quick_pay_amount_4',
        'quick_pay_amount_5',
        'quick_pay_amount_6',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_types' => 'array',
        'payment_methods' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'cash_difference_threshold' => 'decimal:2',
        'quick_pay_amount_1' => 'decimal:2',
        'quick_pay_amount_2' => 'decimal:2',
        'quick_pay_amount_3' => 'decimal:2',
        'quick_pay_amount_4' => 'decimal:2',
        'quick_pay_amount_5' => 'decimal:2',
        'quick_pay_amount_6' => 'decimal:2',
    ];
}
