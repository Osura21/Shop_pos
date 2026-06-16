<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_tax', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('tax_id')->index();
            $table->timestamps();

            $table->unique(['product_id', 'tax_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tax');
    }
};