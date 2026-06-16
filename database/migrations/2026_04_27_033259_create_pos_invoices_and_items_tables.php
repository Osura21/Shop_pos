<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('pos_kitchen_ticket_id')->nullable()->constrained('pos_kitchen_tickets')->nullOnDelete();
            $table->foreignId('pos_transaction_id')->nullable()->constrained('pos_transactions')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('invoice_no')->unique();
            $table->string('seller_name')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('type', 30)->default('simplified');
            $table->string('status', 30)->default('issued');
            $table->string('purpose', 30)->default('original');
            $table->string('kind', 30)->default('standard');
            $table->string('currency_code', 10)->default('LKR');
            $table->decimal('currency_rate', 14, 4)->default(1);
            $table->decimal('cost_price', 14, 3)->default(0);
            $table->decimal('revenue', 14, 3)->default(0);
            $table->decimal('subtotal', 14, 3)->default(0);
            $table->decimal('tax_total', 14, 3)->default(0);
            $table->decimal('total', 14, 3)->default(0);
            $table->decimal('paid_amount', 14, 3)->default(0);
            $table->decimal('refunded_amount', 14, 3)->default(0);
            $table->decimal('net_paid', 14, 3)->default(0);
            $table->json('payments')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_invoice_id')->constrained('pos_invoices')->cascadeOnDelete();
            $table->string('product_name');
            $table->string('status', 30)->nullable();
            $table->decimal('unit_price', 14, 3)->default(0);
            $table->decimal('qty', 14, 3)->default(1);
            $table->decimal('subtotal', 14, 3)->default(0);
            $table->decimal('tax_total', 14, 3)->default(0);
            $table->decimal('line_total', 14, 3)->default(0);
            $table->decimal('cost_price', 14, 3)->default(0);
            $table->decimal('revenue', 14, 3)->default(0);
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_invoice_items');
        Schema::dropIfExists('pos_invoices');
    }
};