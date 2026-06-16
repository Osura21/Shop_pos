<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('loyalty_program_id')
                ->constrained('loyalty_programs')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('benefits')->nullable();
            $table->decimal('minimum_spend', 14, 3)->default(0);
            $table->decimal('secondary_minimum_spend', 14, 3)->nullable();
            $table->decimal('multiplier', 8, 3)->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('icon_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'loyalty_program_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};