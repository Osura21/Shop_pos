<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorMailSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'active',
        'from_address',
        'from_name',
        'mail_method',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'mail_encryption',
        'to_addresses',
        'cc_addresses',
        'bcc_addresses',
    ];

    protected $casts = [
        'active' => 'boolean',
        'smtp_port' => 'integer',
        'smtp_password' => 'encrypted',
    ];

    public function applyToConfig(): void
    {
        config([
            'mail.default' => $this->mail_method ?: config('mail.default'),
            'mail.from.address' => $this->from_address ?: config('mail.from.address'),
            'mail.from.name' => $this->from_name ?: config('mail.from.name'),
        ]);

        if ($this->mail_method !== 'smtp') {
            return;
        }

        config([
            'mail.mailers.smtp.host' => $this->smtp_host,
            'mail.mailers.smtp.port' => $this->smtp_port,
            'mail.mailers.smtp.username' => $this->smtp_username,
            'mail.mailers.smtp.password' => $this->smtp_password,
            'mail.mailers.smtp.encryption' => $this->mail_encryption,
        ]);

        app('mail.manager')->purge($this->mail_method);
    }
}
