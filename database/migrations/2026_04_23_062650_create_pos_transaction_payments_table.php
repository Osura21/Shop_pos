<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_transaction_id');
            $table->string('payment_method', 40);
            $table->decimal('amount', 14, 3)->default(0);
            $table->string('transaction_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('pos_transaction_id', 'ptp_transaction_fk')
                ->references('id')
                ->on('pos_transactions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_transaction_payments', function (Blueprint $table) {
            $table->dropForeign('ptp_transaction_fk');
        });

        Schema::dropIfExists('pos_transaction_payments');
    }
};