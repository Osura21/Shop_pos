<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_card_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('prefix', 20);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('value', 14, 3)->default(0);
            $table->unsignedInteger('cards_generated')->default(0);
            $table->unsignedInteger('cards_used')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gift_card_batch_id')->nullable()->constrained('gift_card_batches')->nullOnDelete();
            $table->string('code')->unique();
            $table->decimal('initial_balance', 14, 3)->default(0);
            $table->decimal('current_balance', 14, 3)->default(0);
            $table->string('status', 30)->default('active');
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'expires_at']);
        });

        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gift_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_transaction_id')->nullable()->constrained('pos_transactions')->nullOnDelete();
            $table->unsignedBigInteger('pos_transaction_payment_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('type', 30);
            $table->decimal('amount', 14, 3)->default(0);
            $table->decimal('balance_before', 14, 3)->default(0);
            $table->decimal('balance_after', 14, 3)->default(0);
            $table->text('note')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->foreign('pos_transaction_payment_id', 'gct_payment_fk')
                ->references('id')
                ->on('pos_transaction_payments')
                ->nullOnDelete();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::table('gift_card_transactions', function (Blueprint $table) {
            $table->dropForeign('gct_payment_fk');
        });

        Schema::dropIfExists('gift_card_transactions');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('gift_card_batches');
    }
};
