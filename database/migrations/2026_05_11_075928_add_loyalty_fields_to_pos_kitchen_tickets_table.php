<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            $table->foreignId('loyalty_reward_id')
                ->nullable()
                ->after('discount_total')
                ->constrained('loyalty_rewards')
                ->nullOnDelete();

            $table->unsignedInteger('loyalty_points_redeemed')
                ->default(0)
                ->after('loyalty_reward_id');

            $table->decimal('loyalty_discount_total', 14, 3)
                ->default(0)
                ->after('loyalty_points_redeemed');
        });
    }

    public function down(): void
    {
        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loyalty_reward_id');

            $table->dropColumn([
                'loyalty_points_redeemed',
                'loyalty_discount_total',
            ]);
        });
    }
};