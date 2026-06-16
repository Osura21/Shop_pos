<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_kitchen_ticket_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_kitchen_ticket_items', 'status')) {
                $table->string('status', 30)->default('pending')->after('currency_code');
            }

            if (!Schema::hasColumn('pos_kitchen_ticket_items', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('pos_kitchen_ticket_items', 'ready_at')) {
                $table->timestamp('ready_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_kitchen_ticket_items', function (Blueprint $table) {
            if (Schema::hasColumn('pos_kitchen_ticket_items', 'ready_at')) {
                $table->dropColumn('ready_at');
            }

            if (Schema::hasColumn('pos_kitchen_ticket_items', 'started_at')) {
                $table->dropColumn('started_at');
            }

            if (Schema::hasColumn('pos_kitchen_ticket_items', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
