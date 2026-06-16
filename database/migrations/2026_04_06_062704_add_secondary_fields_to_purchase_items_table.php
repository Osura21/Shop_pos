<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('secondary_unit_cost', 15, 3)->nullable()->after('unit_cost');
            $table->decimal('secondary_line_total', 15, 3)->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_unit_cost',
                'secondary_line_total',
            ]);
        });
    }
};