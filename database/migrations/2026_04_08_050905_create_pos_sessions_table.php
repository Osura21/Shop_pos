<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('menu_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('dining_table_id')->nullable()->index();

            $table->string('channel')->default('takeaway'); // takeaway, dine_in, pick_up, drive_thru, pre_order, catering
            $table->string('waiter_name')->nullable();
            $table->string('customer_name')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedInteger('guest_count')->default(1);

            $table->enum('discount_mode', ['discount', 'voucher'])->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_value', 15, 3)->nullable();

            $table->decimal('subtotal', 15, 3)->default(0);
            $table->decimal('tax_total', 15, 3)->default(0);
            $table->decimal('discount_total', 15, 3)->default(0);
            $table->decimal('grand_total', 15, 3)->default(0);

            $table->enum('status', ['open', 'hold', 'paid', 'cancelled'])->default('open');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};