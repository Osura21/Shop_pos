<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_session_items', function (Blueprint $table) {
            $table->enum('currency_mode', ['base', 'secondary'])
                ->default('base')
                ->after('product_name');

            $table->string('currency_code', 10)
                ->nullable()
                ->after('currency_mode');
        });
    }

    public function down(): void
    {
        Schema::table('pos_session_items', function (Blueprint $table) {
            $table->dropColumn(['currency_mode', 'currency_code']);
        });
    }
};