<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('pos_register_id')->nullable();
            $table->unsignedBigInteger('pos_session_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('currency_mode', 20)->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->string('payment_mode', 20)->default('full');

            $table->unsignedInteger('total_products')->default(0);

            $table->decimal('subtotal', 14, 3)->default(0);
            $table->decimal('discount_total', 14, 3)->default(0);
            $table->decimal('tax_total', 14, 3)->default(0);
            $table->decimal('grand_total', 14, 3)->default(0);

            $table->decimal('total_paid', 14, 3)->default(0);
            $table->decimal('due_amount', 14, 3)->default(0);
            $table->decimal('customer_given_amount', 14, 3)->default(0);
            $table->decimal('change_return', 14, 3)->default(0);

            $table->boolean('print_bill')->default(false);
            $table->string('status', 20)->default('paid');
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('branch_id', 'pt_branch_fk')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('pos_register_id', 'pt_register_fk')->references('id')->on('pos_registers')->nullOnDelete();
            $table->foreign('pos_session_id', 'pt_session_fk')->references('id')->on('pos_sessions')->nullOnDelete();
            $table->foreign('user_id', 'pt_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropForeign('pt_branch_fk');
            $table->dropForeign('pt_register_fk');
            $table->dropForeign('pt_session_fk');
            $table->dropForeign('pt_user_fk');
        });

        Schema::dropIfExists('pos_transactions');
    }
};