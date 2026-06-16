<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_sessions', 'pms_guest_snapshot')) {
                $table->json('pms_guest_snapshot')->nullable()->after('guest_count');
            }
        });

        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_kitchen_tickets', 'pms_guest_snapshot')) {
                $table->json('pms_guest_snapshot')->nullable()->after('guest_count');
            }
        });

        Schema::table('pos_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_invoices', 'pms_guest_snapshot')) {
                $table->json('pms_guest_snapshot')->nullable()->after('pms_room_charge_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('pos_invoices', 'pms_guest_snapshot')) {
                $table->dropColumn('pms_guest_snapshot');
            }
        });

        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('pos_kitchen_tickets', 'pms_guest_snapshot')) {
                $table->dropColumn('pms_guest_snapshot');
            }
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('pos_sessions', 'pms_guest_snapshot')) {
                $table->dropColumn('pms_guest_snapshot');
            }
        });
    }
};
