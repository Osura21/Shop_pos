<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Branch information
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);

            // Business registration
            $table->string('registration_number')->nullable();
            $table->string('vat_tin')->nullable();

            // Regional settings
            $table->string('currency', 10)->nullable();
            $table->string('timezone')->default('UTC');

            // Address information
            $table->string('country', 120)->nullable();
            $table->string('postal_code', 50)->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 7)->nullable();

            // POS settings
            $table->json('order_types')->nullable();
            $table->json('payment_methods')->nullable();
            $table->decimal('cash_difference_threshold', 12, 2)->default(0);

            $table->decimal('quick_pay_amount_1', 12, 2)->nullable();
            $table->decimal('quick_pay_amount_2', 12, 2)->nullable();
            $table->decimal('quick_pay_amount_3', 12, 2)->nullable();
            $table->decimal('quick_pay_amount_4', 12, 2)->nullable();
            $table->decimal('quick_pay_amount_5', 12, 2)->nullable();
            $table->decimal('quick_pay_amount_6', 12, 2)->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};