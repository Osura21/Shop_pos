<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_session_item_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pos_session_item_id')->index();

            $table->string('option_name');
            $table->string('option_type');
            $table->string('value_label')->nullable();
            $table->text('value_input')->nullable();

            $table->decimal('price', 15, 3)->default(0);
            $table->enum('price_type', ['fixed', 'percentage'])->default('fixed');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_session_item_options');
    }
};