<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('value', 15, 3)->default(0);
            $table->decimal('secondary_value', 15, 3)->nullable();
            $table->decimal('max_discount', 15, 3)->nullable();
            $table->decimal('secondary_max_discount', 15, 3)->nullable();
            $table->decimal('min_spend', 15, 3)->nullable();
            $table->decimal('secondary_min_spend', 15, 3)->nullable();
            $table->decimal('max_spend', 15, 3)->nullable();
            $table->decimal('secondary_max_spend', 15, 3)->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->json('order_types')->nullable();
            $table->json('available_days')->nullable();
            $table->json('category_ids')->nullable();
            $table->json('product_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('promotion_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('value', 15, 3)->default(0);
            $table->decimal('secondary_value', 15, 3)->nullable();
            $table->decimal('max_discount', 15, 3)->nullable();
            $table->decimal('secondary_max_discount', 15, 3)->nullable();
            $table->decimal('min_spend', 15, 3)->nullable();
            $table->decimal('secondary_min_spend', 15, 3)->nullable();
            $table->decimal('max_spend', 15, 3)->nullable();
            $table->decimal('secondary_max_spend', 15, 3)->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->json('order_types')->nullable();
            $table->json('available_days')->nullable();
            $table->json('category_ids')->nullable();
            $table->json('product_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('promotion_redemptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_discount_id')->nullable()->index();
            $table->unsignedBigInteger('promotion_voucher_id')->nullable()->index();
            $table->unsignedBigInteger('pos_session_id')->nullable()->index();
            $table->unsignedBigInteger('pos_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('promotion_type', 20);
            $table->string('promotion_code')->nullable();
            $table->string('currency_mode', 20)->default('base');
            $table->string('currency_code', 10)->nullable();
            $table->decimal('subtotal', 15, 3)->default(0);
            $table->decimal('discount_amount', 15, 3)->default(0);
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('promotion_discount_id')->nullable()->after('discount_value');
            $table->unsignedBigInteger('promotion_voucher_id')->nullable()->after('promotion_discount_id');
            $table->string('promotion_code')->nullable()->after('promotion_voucher_id');
        });

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('promotion_discount_id')->nullable()->after('discount_total');
            $table->unsignedBigInteger('promotion_voucher_id')->nullable()->after('promotion_discount_id');
            $table->string('promotion_code')->nullable()->after('promotion_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn(['promotion_discount_id', 'promotion_voucher_id', 'promotion_code']);
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropColumn(['promotion_discount_id', 'promotion_voucher_id', 'promotion_code']);
        });

        Schema::dropIfExists('promotion_redemptions');
        Schema::dropIfExists('promotion_vouchers');
        Schema::dropIfExists('promotion_discounts');
    }
};
