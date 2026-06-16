<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'from_address',
        'from_name',
        'mail_method',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'mail_encryption',
        'smtp_verify_peer',
    ];

    protected $casts = [
        'smtp_port' => 'integer',
        'smtp_password' => 'encrypted',
        'smtp_verify_peer' => 'boolean',
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
            'mail.mailers.smtp.verify_peer' => $this->smtp_verify_peer,
        ]);
    }
}
