<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_kitchen_tickets', 'cancel_reason')) {
                $table->string('cancel_reason', 120)->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('pos_kitchen_tickets', 'cancel_note')) {
                $table->text('cancel_note')->nullable()->after('cancel_reason');
            }

            if (!Schema::hasColumn('pos_kitchen_tickets', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancel_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('pos_kitchen_tickets', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
            if (Schema::hasColumn('pos_kitchen_tickets', 'cancel_note')) {
                $table->dropColumn('cancel_note');
            }
            if (Schema::hasColumn('pos_kitchen_tickets', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }
        });
    }
};