<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_sessions', 'editing_ticket_id')) {
                $table->unsignedBigInteger('editing_ticket_id')->nullable()->after('menu_id');

                $table->foreign('editing_ticket_id', 'ps_editing_ticket_fk')
                    ->references('id')
                    ->on('pos_kitchen_tickets')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('pos_sessions', 'editing_ticket_id')) {
                $table->dropForeign('ps_editing_ticket_fk');
                $table->dropColumn('editing_ticket_id');
            }
        });
    }
};