<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('vendor_subscription_plan_features');

        Schema::create('vendor_subscription_plan_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_subscription_plan_id');
            $table->string('feature_key');
            $table->string('feature_name');
            $table->string('feature_group')->default('operations');
            $table->string('value_type')->default('boolean');
            $table->boolean('enabled')->default(false);
            $table->boolean('is_unlimited')->default(false);
            $table->decimal('limit_value', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('notes', 500)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['vendor_subscription_plan_id', 'feature_key'], 'vsp_features_plan_key_unique');
            $table->foreign('vendor_subscription_plan_id', 'vsp_features_plan_id_foreign')
                ->references('id')
                ->on('vendor_subscription_plans')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_subscription_plan_features');
    }
};
