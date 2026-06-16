<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_cash_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_cash_movements', 'currency_mode')) {
                $table->string('currency_mode', 20)->nullable()->after('notes');
            }

            if (!Schema::hasColumn('pos_cash_movements', 'currency_code')) {
                $table->string('currency_code', 10)->nullable()->after('currency_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_cash_movements', function (Blueprint $table) {
            if (Schema::hasColumn('pos_cash_movements', 'currency_code')) {
                $table->dropColumn('currency_code');
            }

            if (Schema::hasColumn('pos_cash_movements', 'currency_mode')) {
                $table->dropColumn('currency_mode');
            }
        });
    }
};