<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_mail_settings', function (Blueprint $table) {
            $table->text('to_addresses')->nullable()->after('mail_encryption');
            $table->text('cc_addresses')->nullable()->after('to_addresses');
            $table->text('bcc_addresses')->nullable()->after('cc_addresses');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_mail_settings', function (Blueprint $table) {
            $table->dropColumn(['to_addresses', 'cc_addresses', 'bcc_addresses']);
        });
    }
};
