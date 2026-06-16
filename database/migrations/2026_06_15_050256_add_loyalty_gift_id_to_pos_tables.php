<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->foreignId('loyalty_gift_id')
                ->nullable()
                ->after('loyalty_reward_id')
                ->constrained('loyalty_gifts')
                ->nullOnDelete();
        });

        Schema::table('pos_session_items', function (Blueprint $table) {
            $table->foreignId('loyalty_gift_id')
                ->nullable()
                ->after('product_id')
                ->constrained('loyalty_gifts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_session_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loyalty_gift_id');
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loyalty_gift_id');
        });
    }
};
