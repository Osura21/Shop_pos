<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_invoices', 'discount_total')) {
                $table->decimal('discount_total', 14, 3)->default(0)->after('tax_total');
            }

            if (!Schema::hasColumn('pos_invoices', 'discount_mode')) {
                $table->string('discount_mode', 30)->nullable()->after('discount_total');
            }

            if (!Schema::hasColumn('pos_invoices', 'discount_type')) {
                $table->string('discount_type', 30)->nullable()->after('discount_mode');
            }

            if (!Schema::hasColumn('pos_invoices', 'discount_value')) {
                $table->decimal('discount_value', 14, 3)->nullable()->after('discount_type');
            }

            if (!Schema::hasColumn('pos_invoices', 'promotion_discount_id')) {
                $table->unsignedBigInteger('promotion_discount_id')->nullable()->after('discount_value');
            }

            if (!Schema::hasColumn('pos_invoices', 'promotion_voucher_id')) {
                $table->unsignedBigInteger('promotion_voucher_id')->nullable()->after('promotion_discount_id');
            }

            if (!Schema::hasColumn('pos_invoices', 'promotion_code')) {
                $table->string('promotion_code', 80)->nullable()->after('promotion_voucher_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('pos_invoices', 'discount_total')) {
                $table->dropColumn([
                    'discount_total',
                    'discount_mode',
                    'discount_type',
                    'discount_value',
                    'promotion_discount_id',
                    'promotion_voucher_id',
                    'promotion_code',
                ]);
            }
        });
    }
};