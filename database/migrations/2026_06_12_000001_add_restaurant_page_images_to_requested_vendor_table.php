<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('requested_vendor')) {
            return;
        }

        Schema::table('requested_vendor', function (Blueprint $table) {
            if (! Schema::hasColumn('requested_vendor', 'restaurant_page_image_paths')) {
                $table->json('restaurant_page_image_paths')->nullable()->after('business_photo_paths');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('requested_vendor') || ! Schema::hasColumn('requested_vendor', 'restaurant_page_image_paths')) {
            return;
        }

        Schema::table('requested_vendor', function (Blueprint $table) {
            $table->dropColumn('restaurant_page_image_paths');
        });
    }
};
