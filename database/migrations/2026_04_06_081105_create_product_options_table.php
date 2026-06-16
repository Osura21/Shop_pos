<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();

            $table->string('template_key')->nullable();
            $table->string('name');
            $table->string('type'); // text, textarea, select, multiple_select, checkbox, radio_button, date, time

            $table->boolean('is_required')->default(false);

            // for text/textarea/date/time options
            $table->decimal('base_price', 15, 3)->default(0);
            $table->decimal('secondary_price', 15, 3)->nullable();
            $table->enum('price_type', ['fixed', 'percentage'])->default('fixed');

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_collapsed')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};