<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->foreignId('loyalty_program_id')
                ->constrained('loyalty_programs')
                ->cascadeOnDelete();

            $table->foreignId('loyalty_tier_id')
                ->nullable()
                ->constrained('loyalty_tiers')
                ->nullOnDelete();

            $table->integer('points_balance')->default(0);
            $table->integer('lifetime_points')->default(0);

            $table->decimal('lifetime_spend', 14, 3)->default(0);
            $table->decimal('secondary_lifetime_spend', 14, 3)->default(0);

            $table->timestamp('last_earned_at')->nullable();
            $table->timestamp('last_redeemed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'customer_id', 'loyalty_program_id'],
                'lc_unique_customer_program'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_customers');
    }
};