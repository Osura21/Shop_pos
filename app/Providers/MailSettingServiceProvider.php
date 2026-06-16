<?php

namespace App\Providers;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class MailSettingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('mail_settings')) {
                return;
            }

            MailSetting::query()->first()?->applyToConfig();
        } catch (\Throwable) {
            // Keep the app bootable during installs, migrations, or database outages.
        }
    }
}
