<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('sku');
            $table->string('unit_type', 20)->default('pcs')->after('brand');
            $table->decimal('cost_price', 15, 3)->default(0)->after('secondary_price');
            $table->decimal('current_stock', 15, 3)->default(0)->after('cost_price');
            $table->decimal('reorder_level', 15, 3)->default(0)->after('current_stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'brand',
                'unit_type',
                'cost_price',
                'current_stock',
                'reorder_level',
            ]);
        });
    }
};
