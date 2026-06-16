<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_kitchen_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->unsignedBigInteger('pos_session_id')->nullable();
            $table->unsignedBigInteger('pos_register_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('dining_table_id')->nullable();

            $table->string('channel')->nullable();
            $table->string('waiter_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('car_plate')->nullable();
            $table->text('car_description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('guest_count')->default(1);

            $table->string('currency_mode', 20)->nullable();
            $table->string('currency_code', 10)->nullable();

            $table->decimal('subtotal', 14, 3)->default(0);
            $table->decimal('tax_total', 14, 3)->default(0);
            $table->decimal('discount_total', 14, 3)->default(0);
            $table->decimal('grand_total', 14, 3)->default(0);

            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'cancelled'])->default('pending');
            $table->timestamp('sent_to_kitchen_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();

            $table->timestamps();

            $table->foreign('pos_session_id', 'pkt_pos_session_fk')
                ->references('id')->on('pos_sessions')->nullOnDelete();

            $table->foreign('pos_register_id', 'pkt_pos_register_fk')
                ->references('id')->on('pos_registers')->nullOnDelete();

            $table->foreign('branch_id', 'pkt_branch_fk')
                ->references('id')->on('branches')->nullOnDelete();

            $table->foreign('customer_id', 'pkt_customer_fk')
                ->references('id')->on('customers')->nullOnDelete();

            $table->foreign('dining_table_id', 'pkt_table_fk')
                ->references('id')->on('dining_tables')->nullOnDelete();
        });

        Schema::create('pos_kitchen_ticket_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_kitchen_ticket_id');
            $table->unsignedBigInteger('product_id')->nullable();

            $table->string('product_name');
            $table->string('image_url')->nullable();
            $table->decimal('qty', 14, 3)->default(1);
            $table->decimal('unit_price', 14, 3)->default(0);
            $table->decimal('option_total', 14, 3)->default(0);
            $table->decimal('line_subtotal', 14, 3)->default(0);
            $table->decimal('tax_total', 14, 3)->default(0);
            $table->decimal('line_total', 14, 3)->default(0);
            $table->json('tax_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->string('currency_mode', 20)->nullable();
            $table->string('currency_code', 10)->nullable();
            $table->timestamps();

            $table->foreign('pos_kitchen_ticket_id', 'pkti_ticket_fk')
                ->references('id')->on('pos_kitchen_tickets')->cascadeOnDelete();

            $table->foreign('product_id', 'pkti_product_fk')
                ->references('id')->on('products')->nullOnDelete();
        });

        Schema::create('pos_kitchen_ticket_item_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_kitchen_ticket_item_id');

            $table->string('option_name');
            $table->string('option_type')->nullable();
            $table->string('value_label')->nullable();
            $table->text('value_input')->nullable();
            $table->decimal('price', 14, 3)->default(0);
            $table->string('price_type', 20)->nullable();
            $table->timestamps();

            $table->foreign('pos_kitchen_ticket_item_id', 'pktio_item_fk')
                ->references('id')->on('pos_kitchen_ticket_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pos_kitchen_ticket_item_options', function (Blueprint $table) {
            $table->dropForeign('pktio_item_fk');
        });

        Schema::table('pos_kitchen_ticket_items', function (Blueprint $table) {
            $table->dropForeign('pkti_ticket_fk');
            $table->dropForeign('pkti_product_fk');
        });

        Schema::table('pos_kitchen_tickets', function (Blueprint $table) {
            $table->dropForeign('pkt_pos_session_fk');
            $table->dropForeign('pkt_pos_register_fk');
            $table->dropForeign('pkt_branch_fk');
            $table->dropForeign('pkt_customer_fk');
            $table->dropForeign('pkt_table_fk');
        });

        Schema::dropIfExists('pos_kitchen_ticket_item_options');
        Schema::dropIfExists('pos_kitchen_ticket_items');
        Schema::dropIfExists('pos_kitchen_tickets');
    }
};