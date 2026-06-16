<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gift_card_batches', function (Blueprint $table) {
            $table->string('base_currency_code', 10)->nullable()->after('branch_id');
            $table->string('secondary_currency_code', 10)->nullable()->after('base_currency_code');
            $table->decimal('secondary_value', 14, 3)->nullable()->after('value');
        });

        Schema::table('gift_cards', function (Blueprint $table) {
            $table->string('base_currency_code', 10)->nullable()->after('gift_card_batch_id');
            $table->string('secondary_currency_code', 10)->nullable()->after('base_currency_code');
            $table->decimal('secondary_initial_balance', 14, 3)->nullable()->after('current_balance');
            $table->decimal('secondary_current_balance', 14, 3)->nullable()->after('secondary_initial_balance');
        });

        Schema::table('gift_card_transactions', function (Blueprint $table) {
            $table->string('currency_mode', 20)->default('base')->after('created_by');
            $table->string('currency_code', 10)->nullable()->after('currency_mode');
        });
    }

    public function down(): void
    {
        Schema::table('gift_card_transactions', function (Blueprint $table) {
            $table->dropColumn(['currency_mode', 'currency_code']);
        });

        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropColumn([
                'base_currency_code',
                'secondary_currency_code',
                'secondary_initial_balance',
                'secondary_current_balance',
            ]);
        });

        Schema::table('gift_card_batches', function (Blueprint $table) {
            $table->dropColumn(['base_currency_code', 'secondary_currency_code', 'secondary_value']);
        });
    }
};
