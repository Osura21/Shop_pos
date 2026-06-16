<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            $table->string('pms_posting_status')->nullable()->after('cancelled_at');
            $table->timestamp('pms_posted_at')->nullable()->after('pms_posting_status');
            $table->json('pms_response')->nullable()->after('pms_posted_at');
            $table->string('pms_booking_id')->nullable()->after('pms_response');
            $table->string('pms_room_key_id')->nullable()->after('pms_booking_id');
            $table->decimal('paid_amount', 14, 3)->default(0)->after('pms_room_key_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'pms_posting_status',
                'pms_posted_at',
                'pms_response',
                'pms_booking_id',
                'pms_room_key_id',
                'paid_amount',
            ]);
        });
    }
};
