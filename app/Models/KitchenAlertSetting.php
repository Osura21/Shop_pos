<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenAlertSetting extends Model
{
    public const SOUND_OPTIONS = [
        'bell' => 'Bell',
        'chime' => 'Chime',
        'ding' => 'Ding',
        'pulse' => 'Pulse',
        'classic' => 'Classic Beep',
     
    ];

    protected $fillable = [
        'tenant_id',
        'sound_enabled',
        'sound',
    ];

    protected $casts = [
        'sound_enabled' => 'boolean',
    ];

    public static function options(): array
    {
        return collect(self::SOUND_OPTIONS)
            ->map(fn (string $label, string $value) => compact('label', 'value'))
            ->values()
            ->all();
    }
}
