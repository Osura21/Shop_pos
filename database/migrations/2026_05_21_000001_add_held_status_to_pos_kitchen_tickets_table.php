<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE `pos_kitchen_tickets` MODIFY `status` ENUM('pending', 'held', 'preparing', 'ready', 'served', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("UPDATE `pos_kitchen_tickets` SET `status` = 'pending' WHERE `status` = 'held'");
        DB::statement("ALTER TABLE `pos_kitchen_tickets` MODIFY `status` ENUM('pending', 'preparing', 'ready', 'served', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
