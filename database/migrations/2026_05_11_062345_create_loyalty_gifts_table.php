<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_gifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnDelete();

            $table->foreignId('loyalty_program_id')
                ->constrained('loyalty_programs')
                ->cascadeOnDelete();

            $table->foreignId('loyalty_reward_id')
                ->constrained('loyalty_rewards')
                ->cascadeOnDelete();

            $table->foreignId('pos_transaction_id')
                ->nullable()
                ->constrained('pos_transactions')
                ->nullOnDelete();

            $table->string('status', 30)->default('available');
            $table->string('type', 40);
            $table->unsignedInteger('points_spent')->default(0);
            $table->string('code', 80)->nullable()->index();
            $table->json('payload')->nullable();

            $table->timestamp('valid_until')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_gifts');
    }
};