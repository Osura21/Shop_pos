<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('ingredient_id')->index();

            $table->string('type', 50);
            $table->decimal('quantity');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_name')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'ingredient_id']);
            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};