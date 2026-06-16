<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('requested_vendor')) {
            return;
        }

        Schema::table('requested_vendor', function (Blueprint $table) {
            if (! Schema::hasColumn('requested_vendor', 'legal_business_name')) {
                $table->string('legal_business_name')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('requested_vendor', 'store_display_name')) {
                $table->string('store_display_name')->nullable()->after('legal_business_name');
            }

            if (! Schema::hasColumn('requested_vendor', 'business_address')) {
                $table->text('business_address')->nullable()->after('store_display_name');
            }

            if (! Schema::hasColumn('requested_vendor', 'business_email')) {
                $table->string('business_email')->nullable()->after('business_address');
            }

            if (! Schema::hasColumn('requested_vendor', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('business_email');
            }

            if (! Schema::hasColumn('requested_vendor', 'business_registration_number')) {
                $table->string('business_registration_number')->nullable()->after('owner_name');
            }

            if (! Schema::hasColumn('requested_vendor', 'city')) {
                $table->string('city')->nullable()->after('business_registration_number');
            }

            if (! Schema::hasColumn('requested_vendor', 'country')) {
                $table->string('country')->default('Sri Lanka')->after('city');
            }

            if (! Schema::hasColumn('requested_vendor', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('country');
            }

            if (! Schema::hasColumn('requested_vendor', 'search_location')) {
                $table->string('search_location')->nullable()->after('country_code');
            }

            if (! Schema::hasColumn('requested_vendor', 'google_place_id')) {
                $table->string('google_place_id')->nullable()->after('search_location');
            }

            if (! Schema::hasColumn('requested_vendor', 'postal_code')) {
                $table->string('postal_code', 40)->nullable()->after('google_place_id');
            }

            if (! Schema::hasColumn('requested_vendor', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('postal_code');
            }

            if (! Schema::hasColumn('requested_vendor', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }

            if (! Schema::hasColumn('requested_vendor', 'state_province')) {
                $table->string('state_province', 120)->nullable()->after('address_line_2');
            }

            if (! Schema::hasColumn('requested_vendor', 'food_types')) {
                $table->json('food_types')->nullable()->after('state_province');
            }

            if (! Schema::hasColumn('requested_vendor', 'opening_from')) {
                $table->string('opening_from')->nullable()->after('food_types');
            }

            if (! Schema::hasColumn('requested_vendor', 'opening_to')) {
                $table->string('opening_to')->nullable()->after('opening_from');
            }

            if (! Schema::hasColumn('requested_vendor', 'service_options')) {
                $table->json('service_options')->nullable()->after('opening_to');
            }

            if (! Schema::hasColumn('requested_vendor', 'terms_accepted')) {
                $table->boolean('terms_accepted')->default(false)->after('service_options');
            }

            if (! Schema::hasColumn('requested_vendor', 'business_logo_path')) {
                $table->string('business_logo_path')->nullable()->after('terms_accepted');
            }

            if (! Schema::hasColumn('requested_vendor', 'business_photo_paths')) {
                $table->json('business_photo_paths')->nullable()->after('business_logo_path');
            }

            if (! Schema::hasColumn('requested_vendor', 'restaurant_page_image_paths')) {
                $table->json('restaurant_page_image_paths')->nullable()->after('business_photo_paths');
            }

            if (! Schema::hasColumn('requested_vendor', 'business_license_path')) {
                $table->string('business_license_path')->nullable()->after('restaurant_page_image_paths');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('requested_vendor')) {
            return;
        }

        Schema::table('requested_vendor', function (Blueprint $table) {
            foreach ([
                'business_license_path',
                'restaurant_page_image_paths',
                'business_photo_paths',
                'business_logo_path',
                'terms_accepted',
                'service_options',
                'opening_to',
                'opening_from',
                'food_types',
                'country',
                'state_province',
                'address_line_2',
                'address_line_1',
                'postal_code',
                'google_place_id',
                'search_location',
                'country_code',
                'city',
                'business_registration_number',
                'owner_name',
                'business_email',
                'business_address',
                'store_display_name',
                'legal_business_name',
            ] as $column) {
                if (Schema::hasColumn('requested_vendor', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
