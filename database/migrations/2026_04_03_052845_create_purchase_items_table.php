<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id')->index();
            $table->unsignedBigInteger('ingredient_id')->index();

            $table->decimal('quantity', 18, 3);
            $table->decimal('received_quantity', 18, 3)->default(0);
            $table->decimal('unit_cost', 18, 3)->default(0);
            $table->decimal('line_total', 18, 3)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};