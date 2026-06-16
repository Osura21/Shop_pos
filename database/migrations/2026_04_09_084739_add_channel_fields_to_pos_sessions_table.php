<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->string('car_plate')->nullable()->after('customer_name');
            $table->text('car_description')->nullable()->after('car_plate');
            $table->dateTime('scheduled_at')->nullable()->after('car_description');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'car_plate',
                'car_description',
                'scheduled_at',
            ]);
        });
    }
};