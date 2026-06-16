<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_transactions', function (Blueprint $table) {
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
                ->nullable()
                ->constrained('loyalty_rewards')
                ->nullOnDelete();

            $table->foreignId('loyalty_gift_id')
                ->nullable()
                ->constrained('loyalty_gifts')
                ->nullOnDelete();

            $table->foreignId('pos_transaction_id')
                ->nullable()
                ->constrained('pos_transactions')
                ->nullOnDelete();

            $table->string('type', 30);
            $table->string('description')->nullable();

            $table->integer('points')->default(0);
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);

            $table->decimal('amount', 14, 3)->nullable();
            $table->string('currency_mode', 20)->nullable();
            $table->string('currency_code', 10)->nullable();

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'customer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};