<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_invoices', 'pms_posting_status')) {
                $table->string('pms_posting_status', 30)->nullable()->after('notes')->index();
            }

            if (!Schema::hasColumn('pos_invoices', 'pms_posted_at')) {
                $table->timestamp('pms_posted_at')->nullable()->after('pms_posting_status');
            }

            if (!Schema::hasColumn('pos_invoices', 'pms_response')) {
                $table->json('pms_response')->nullable()->after('pms_posted_at');
            }

            if (!Schema::hasColumn('pos_invoices', 'pms_booking_id')) {
                $table->string('pms_booking_id')->nullable()->after('pms_response')->index();
            }

            if (!Schema::hasColumn('pos_invoices', 'pms_room_key_id')) {
                $table->string('pms_room_key_id')->nullable()->after('pms_booking_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_invoices', function (Blueprint $table) {
            foreach (['pms_room_key_id', 'pms_booking_id', 'pms_response', 'pms_posted_at', 'pms_posting_status'] as $column) {
                if (Schema::hasColumn('pos_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
