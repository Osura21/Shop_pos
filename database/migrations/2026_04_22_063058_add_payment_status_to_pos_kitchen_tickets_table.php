<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_kitchen_tickets', 'payment_status')) {
                $table->string('payment_status', 20)->default('unpaid')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('pos_kitchen_tickets', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};