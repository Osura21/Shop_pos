<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_session_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_session_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();

            $table->string('product_name');
            $table->string('image_url')->nullable();

            $table->decimal('qty', 15, 3)->default(1);
            $table->decimal('unit_price', 15, 3)->default(0);
            $table->decimal('option_total', 15, 3)->default(0);
            $table->decimal('line_subtotal', 15, 3)->default(0);
            $table->decimal('tax_total', 15, 3)->default(0);
            $table->decimal('line_total', 15, 3)->default(0);

            $table->json('tax_snapshot')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_session_items');
    }
};