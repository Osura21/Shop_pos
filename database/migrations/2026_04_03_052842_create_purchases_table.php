<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_id')->index();

            $table->string('reference_no')->unique();
            $table->string('status', 50)->default('pending'); // pending, partially_received, received, cancelled
            $table->date('expected_at')->nullable();
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 18, 3)->default(0);
            $table->decimal('tax', 18, 3)->default(0);
            $table->decimal('discount', 18, 3)->default(0);
            $table->decimal('total', 18, 3)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};