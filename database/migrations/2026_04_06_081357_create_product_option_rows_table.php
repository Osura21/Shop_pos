<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_option_id')->index();

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
        Schema::dropIfExists('product_option_rows');
    }
};