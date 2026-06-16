<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->string('kitchen_status')->nullable()->after('status');
            $table->timestamp('sent_to_kitchen_at')->nullable()->after('kitchen_status');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropColumn(['kitchen_status', 'sent_to_kitchen_at']);
        });
    }
};