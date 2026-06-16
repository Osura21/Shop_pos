<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('requested_vendor')) {
            return;
        }

        Schema::create('requested_vendor', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('admin_name');
            $table->string('slug')->unique();
            $table->string('status')->default('pending');
            $table->string('email');
            $table->string('phone');
            $table->string('legal_business_name')->nullable();
            $table->string('store_display_name')->nullable();
            $table->text('business_address')->nullable();
            $table->string('business_email')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Sri Lanka');
            $table->string('country_code', 2)->nullable();
            $table->string('search_location')->nullable();
            $table->string('google_place_id')->nullable();
            $table->string('postal_code', 40)->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('state_province', 120)->nullable();
            $table->json('food_types')->nullable();
            $table->string('opening_from')->nullable();
            $table->string('opening_to')->nullable();
            $table->json('service_options')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->string('business_logo_path')->nullable();
            $table->json('business_photo_paths')->nullable();
            $table->json('restaurant_page_image_paths')->nullable();
            $table->string('business_license_path')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requested_vendor');
    }
};
