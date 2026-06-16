<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_transaction_payments', function (Blueprint $table) {
            $table->foreignId('gift_card_id')->nullable()->after('transaction_id')->constrained('gift_cards')->nullOnDelete();
            $table->string('gift_card_code')->nullable()->after('gift_card_id');
            $table->json('meta')->nullable()->after('gift_card_code');
        });
    }

    public function down(): void
    {
        Schema::table('pos_transaction_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gift_card_id');
            $table->dropColumn(['gift_card_code', 'meta']);
        });
    }
};
