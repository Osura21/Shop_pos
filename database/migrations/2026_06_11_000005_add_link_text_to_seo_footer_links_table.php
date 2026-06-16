<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_footer_links', function (Blueprint $table) {
            if (! Schema::hasColumn('seo_footer_links', 'link_text')) {
                $table->string('link_text', 160)->nullable()->after('location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_footer_links', function (Blueprint $table) {
            if (Schema::hasColumn('seo_footer_links', 'link_text')) {
                $table->dropColumn('link_text');
            }
        });
    }
};
