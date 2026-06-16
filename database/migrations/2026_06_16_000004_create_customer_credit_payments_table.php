<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credit_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('pos_register_id')->nullable();
            $table->unsignedBigInteger('pos_session_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->decimal('amount', 14, 3)->default(0);
            $table->string('payment_method', 40)->default('cash');
            $table->string('receipt_no', 80)->unique();
            $table->string('reference', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id', 'ccp_customer_fk')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('branch_id', 'ccp_branch_fk')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('pos_register_id', 'ccp_register_fk')->references('id')->on('pos_registers')->nullOnDelete();
            $table->foreign('pos_session_id', 'ccp_session_fk')->references('id')->on('pos_sessions')->nullOnDelete();
            $table->foreign('user_id', 'ccp_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_payments', function (Blueprint $table) {
            $table->dropForeign('ccp_customer_fk');
            $table->dropForeign('ccp_branch_fk');
            $table->dropForeign('ccp_register_fk');
            $table->dropForeign('ccp_session_fk');
            $table->dropForeign('ccp_user_fk');
        });

        Schema::dropIfExists('customer_credit_payments');
    }
};
