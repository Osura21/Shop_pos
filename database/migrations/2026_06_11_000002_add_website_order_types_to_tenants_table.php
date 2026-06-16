<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'website_order_types')) {
                $table->json('website_order_types')->nullable()->after('contact');
            }
        });

        DB::table('tenants')
            ->whereNull('website_order_types')
            ->update([
                'website_order_types' => json_encode(['delivery', 'pickup', 'scheduled']),
            ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'website_order_types')) {
                $table->dropColumn('website_order_types');
            }
        });
    }
};
