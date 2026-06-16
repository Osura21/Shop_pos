<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('name');
            $table->string('customer_type', 50)->default('regular');

            // save full phone in ONE column only
            $table->string('phone', 50)->nullable();

            $table->date('birthdate')->nullable();
            $table->string('gender', 30)->nullable();
            $table->boolean('is_active')->default(true);

            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->text('note')->nullable();

            $table->string('password')->nullable();

            $table->string('registration_number', 100)->nullable();
            $table->string('vat_tin', 100)->nullable();

            $table->string('avatar')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'username']);
            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};