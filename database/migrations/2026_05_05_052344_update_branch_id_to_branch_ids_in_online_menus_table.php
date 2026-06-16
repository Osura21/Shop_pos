<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_menus', function (Blueprint $table) {
            $table->json('branch_ids')->nullable()->after('tenant_id');
            $table->dropColumn('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('online_menus', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->dropColumn('branch_ids');
        });
    }
};