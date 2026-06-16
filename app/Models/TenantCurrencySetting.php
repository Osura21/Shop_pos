<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantCurrencySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'base_currency_code',
        'secondary_currency_code',
    ];

    public static function getCurrencySymbol(?string $currencyCode): ?string
    {
        $currencyCode = strtoupper($currencyCode ?? '');

        $symbols = [
            'LKR' => 'Rs',
            'RS' => 'Rs',
            'USD' => '$',
            'EUR' => "\u{20AC}",
            'GBP' => "\u{00A3}",
            'SAR' => "\u{FDFC}",
            'AED' => 'AED',
            'JOD' => 'JD',
            'INR' => "\u{20B9}",
            'JPY' => "\u{00A5}",
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'CHF',
            'CNY' => "\u{00A5}",
            'QAR' => 'QR',
            'KWD' => 'KD',
        ];

        return $symbols[$currencyCode] ?? $currencyCode;
    }
}
