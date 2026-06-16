<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('floor_id')->index();
            $table->unsignedBigInteger('zone_id')->index();
            $table->string('name');
            $table->enum('shape', ['square', 'rectangle', 'round'])->default('square');
            $table->unsignedInteger('capacity')->default(1);
            $table->enum('status', ['available', 'occupied'])->default('available');
            $table->string('qr_token')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'branch_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_tables');
    }
};