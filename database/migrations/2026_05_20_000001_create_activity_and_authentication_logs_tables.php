<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_type')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role')->nullable();
            $table->string('log_name')->index();
            $table->string('event', 40)->index();
            $table->string('subject_type')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('subject_label')->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('http_method', 12)->nullable();
            $table->text('url')->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_desktop')->default(false);
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'log_name']);
            $table->index(['tenant_id', 'event']);
        });

        Schema::create('authentication_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_type')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_desktop')->default(false);
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->timestamp('login_at')->nullable()->index();
            $table->timestamp('logout_at')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'login_at']);
            $table->index(['tenant_id', 'logout_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authentication_logs');
        Schema::dropIfExists('activity_logs');
    }
};
