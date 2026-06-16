<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_invoices', 'pms_room_charge_amount')) {
                $table->decimal('pms_room_charge_amount', 14, 3)->default(0)->after('pms_room_key_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('pos_invoices', 'pms_room_charge_amount')) {
                $table->dropColumn('pms_room_charge_amount');
            }
        });
    }
};
