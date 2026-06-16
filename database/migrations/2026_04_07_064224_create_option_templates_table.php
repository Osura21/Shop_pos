<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_templates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->index();

            $table->string('name');
            $table->string('type'); // text, textarea, select, multiple_select, checkbox, radio_button, date, time
            $table->boolean('is_required')->default(false);

            // used for non-list types: text, textarea, date, time
            $table->decimal('base_price', 15, 3)->default(0);
            $table->decimal('secondary_price', 15, 3)->nullable();
            $table->enum('price_type', ['fixed', 'percentage'])->default('fixed');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_templates');
    }
};