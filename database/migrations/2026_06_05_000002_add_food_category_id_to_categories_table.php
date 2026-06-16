<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('food_category_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('food_categories')
                ->nullOnDelete();

            $table->index(['tenant_id', 'food_category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'food_category_id']);
            $table->dropConstrainedForeignId('food_category_id');
        });
    }
};
