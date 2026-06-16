<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('base_currency_code', 10)->nullable()->after('supplier_id');
            $table->string('secondary_currency_code', 10)->nullable()->after('base_currency_code');

            $table->decimal('secondary_tax_value', 15, 3)->nullable()->after('tax_value');
            $table->decimal('secondary_discount_value', 15, 3)->nullable()->after('discount_value');

            $table->decimal('secondary_subtotal', 15, 3)->nullable()->after('subtotal');
            $table->decimal('secondary_tax', 15, 3)->nullable()->after('tax');
            $table->decimal('secondary_discount', 15, 3)->nullable()->after('discount');
            $table->decimal('secondary_total', 15, 3)->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'base_currency_code',
                'secondary_currency_code',
                'secondary_tax_value',
                'secondary_discount_value',
                'secondary_subtotal',
                'secondary_tax',
                'secondary_discount',
                'secondary_total',
            ]);
        });
    }
};