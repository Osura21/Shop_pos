<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();

            $table->string('name');
            $table->string('code');

            $table->decimal('rate', 8, 3)->default(0);

            // exclusive | inclusive
            $table->enum('type', ['exclusive', 'inclusive'])->default('exclusive');

            $table->boolean('is_compound')->default(false);
            $table->boolean('is_global')->default(false);
            $table->boolean('is_active')->default(true);

            // takeaway, dine_in, pick_up, drive_thru, pre_order, catering
            $table->json('order_types')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};