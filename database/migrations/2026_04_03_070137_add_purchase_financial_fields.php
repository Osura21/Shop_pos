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
      Schema::table('purchases', function (Blueprint $table) {
    $table->string('currency', 10)->nullable()->after('supplier_id');

    $table->enum('tax_type', ['fixed', 'percentage'])->default('fixed');
    $table->decimal('tax_value', 18, 3)->default(0);

    $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
    $table->decimal('discount_value', 18, 3)->default(0);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            //
        });
    }
};
