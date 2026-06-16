<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_template_values', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('option_template_id')->index();

            $table->string('label');
            $table->decimal('base_price', 15, 3)->default(0);
            $table->decimal('secondary_price', 15, 3)->nullable();
            $table->enum('price_type', ['fixed', 'percentage'])->default('fixed');
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_template_values');
    }
};