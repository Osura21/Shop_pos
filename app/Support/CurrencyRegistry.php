<?php

namespace App\Support;

class CurrencyRegistry
{
    public static function all(): array
    {
        return [
            ['code' => 'LKR', 'name' => 'Sri Lankan Rupee', 'symbol' => 'Rs'],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => "\u{20AC}"],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => "\u{00A3}"],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => "\u{FDFC}"],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'AED'],
            ['code' => 'JOD', 'name' => 'Jordanian Dinar', 'symbol' => 'JD'],
            ['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => "\u{20B9}"],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'QR'],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KD'],
        ];
    }

    public static function find(?string $code): ?array
    {
        if (! $code) {
            return null;
        }

        foreach (self::all() as $currency) {
            if ($currency['code'] === strtoupper($code)) {
                return $currency;
            }
        }

        return null;
    }
}
