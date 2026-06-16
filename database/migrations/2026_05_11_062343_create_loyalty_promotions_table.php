<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('loyalty_program_id')
                ->constrained('loyalty_programs')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 40)->default('bonus_points');
            $table->decimal('multiplier', 8, 3)->nullable();
            $table->unsignedInteger('bonus_points')->nullable();

            $table->decimal('minimum_spend', 14, 3)->nullable();
            $table->decimal('secondary_minimum_spend', 14, 3)->nullable();

            $table->json('branch_ids')->nullable();
            $table->json('available_days')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();

            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('customers_count')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

$table->index(
    ['tenant_id', 'loyalty_program_id', 'type', 'is_active'],
    'lp_tenant_program_type_active_idx'
);        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_promotions');
    }
};