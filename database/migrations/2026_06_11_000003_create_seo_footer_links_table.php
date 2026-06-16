<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_footer_links', function (Blueprint $table) {
            $table->id();
            $table->string('country', 120);
            $table->string('country_code', 2)->nullable();
            $table->string('location', 120);
            $table->string('link_text', 160)->nullable();
            $table->string('food_type', 120);
            $table->string('food_type_slug', 140)->nullable();
            $table->string('order_type', 30)->default('delivery');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country', 'location']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_footer_links');
    }
};
