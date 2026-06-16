<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('table_merge_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('table_merge_id')->index();
            $table->unsignedBigInteger('dining_table_id')->index();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['table_merge_id', 'dining_table_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_merge_items');
    }
};