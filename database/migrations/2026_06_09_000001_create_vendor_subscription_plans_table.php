<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name');
            $table->string('plan_code')->unique();
            $table->string('short_description', 500)->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_default')->default(false);
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('yearly_price', 12, 2)->nullable();
            $table->string('yearly_discount_type')->nullable();
            $table->decimal('yearly_discount_value', 12, 2)->nullable();
            $table->string('currency_code', 10)->default('LKR');
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->boolean('auto_renew')->default(true);
            $table->text('cancellation_policy')->nullable();
            $table->text('refund_policy')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('badge')->nullable();
            $table->boolean('highlight_plan')->default(false);
            $table->boolean('most_popular')->default(false);
            $table->string('plan_card_color', 20)->default('#5C2D80');
            $table->string('icon_key')->default('bi-gem');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_subscription_plans');
    }
};
