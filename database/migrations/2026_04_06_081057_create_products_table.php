<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('menu_id')->nullable()->index();

            $table->string('name');
            $table->string('sku');
            $table->text('description')->nullable();

            $table->string('image_path')->nullable();

            // base / secondary standard price
            $table->decimal('base_price', 15, 3)->default(0);
            $table->decimal('secondary_price', 15, 3)->nullable();

            // special price
            $table->enum('special_price_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('base_special_price', 15, 3)->nullable();
            $table->decimal('secondary_special_price', 15, 3)->nullable();

            $table->dateTime('special_price_start')->nullable();
            $table->dateTime('special_price_end')->nullable();

            $table->dateTime('new_from')->nullable();
            $table->dateTime('new_to')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};