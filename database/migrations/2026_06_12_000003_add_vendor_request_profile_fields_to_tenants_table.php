<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'source_requested_vendor_id')) {
                $table->unsignedBigInteger('source_requested_vendor_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('tenants', 'legal_business_name')) {
                $table->string('legal_business_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('tenants', 'store_display_name')) {
                $table->string('store_display_name')->nullable()->after('legal_business_name');
            }

            if (! Schema::hasColumn('tenants', 'business_email')) {
                $table->string('business_email')->nullable()->after('contact');
            }

            if (! Schema::hasColumn('tenants', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('business_email');
            }

            if (! Schema::hasColumn('tenants', 'business_registration_number')) {
                $table->string('business_registration_number')->nullable()->after('owner_name');
            }

            if (! Schema::hasColumn('tenants', 'food_types')) {
                $table->json('food_types')->nullable()->after('website_order_types');
            }

            if (! Schema::hasColumn('tenants', 'opening_from')) {
                $table->string('opening_from')->nullable()->after('food_types');
            }

            if (! Schema::hasColumn('tenants', 'opening_to')) {
                $table->string('opening_to')->nullable()->after('opening_from');
            }

            if (! Schema::hasColumn('tenants', 'business_logo_path')) {
                $table->string('business_logo_path')->nullable()->after('opening_to');
            }

            if (! Schema::hasColumn('tenants', 'business_photo_paths')) {
                $table->json('business_photo_paths')->nullable()->after('business_logo_path');
            }

            if (! Schema::hasColumn('tenants', 'restaurant_page_image_paths')) {
                $table->json('restaurant_page_image_paths')->nullable()->after('business_photo_paths');
            }

            if (! Schema::hasColumn('tenants', 'business_license_path')) {
                $table->string('business_license_path')->nullable()->after('restaurant_page_image_paths');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            foreach ([
                'business_license_path',
                'restaurant_page_image_paths',
                'business_photo_paths',
                'business_logo_path',
                'opening_to',
                'opening_from',
                'food_types',
                'business_registration_number',
                'owner_name',
                'business_email',
                'store_display_name',
                'legal_business_name',
                'source_requested_vendor_id',
            ] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
