<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('loyalty_program_id')
                ->constrained('loyalty_programs')
                ->cascadeOnDelete();

            $table->foreignId('loyalty_tier_id')
                ->nullable()
                ->constrained('loyalty_tiers')
                ->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 40)->default('discount');
            $table->unsignedInteger('points_cost')->default(0);
            $table->string('icon_path')->nullable();
            $table->string('value_type', 30)->nullable();
            $table->decimal('value', 14, 3)->nullable();
            $table->decimal('secondary_value', 14, 3)->nullable();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->decimal('quantity', 14, 3)->nullable();

            $table->foreignId('target_tier_id')
                ->nullable()
                ->constrained('loyalty_tiers')
                ->nullOnDelete();

            $table->string('code_prefix', 30)->nullable();
            $table->unsignedInteger('expires_in_days')->nullable();

            $table->decimal('minimum_order_total', 14, 3)->nullable();
            $table->decimal('secondary_minimum_order_total', 14, 3)->nullable();
            $table->decimal('maximum_order_total', 14, 3)->nullable();
            $table->decimal('secondary_maximum_order_total', 14, 3)->nullable();

            $table->decimal('minimum_spend', 14, 3)->nullable();
            $table->decimal('secondary_minimum_spend', 14, 3)->nullable();

            $table->json('branch_ids')->nullable();
            $table->json('available_days')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->unsignedInteger('max_redemptions_per_order')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();

            $table->unsignedInteger('redeemed_count')->default(0);
            $table->unsignedInteger('customers_count')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

$table->index(
    ['tenant_id', 'loyalty_program_id', 'type', 'is_active'],
    'lr_tenant_program_type_active_idx'
);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};