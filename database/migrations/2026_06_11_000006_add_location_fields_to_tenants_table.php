<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'country')) {
                $table->string('country', 120)->nullable()->after('address');
            }

            if (! Schema::hasColumn('tenants', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('country');
            }

            if (! Schema::hasColumn('tenants', 'search_location')) {
                $table->string('search_location', 255)->nullable()->after('country_code');
            }

            if (! Schema::hasColumn('tenants', 'google_place_id')) {
                $table->string('google_place_id', 255)->nullable()->after('search_location');
            }

            if (! Schema::hasColumn('tenants', 'postal_code')) {
                $table->string('postal_code', 40)->nullable()->after('google_place_id');
            }

            if (! Schema::hasColumn('tenants', 'address_line_1')) {
                $table->string('address_line_1', 255)->nullable()->after('postal_code');
            }

            if (! Schema::hasColumn('tenants', 'address_line_2')) {
                $table->string('address_line_2', 255)->nullable()->after('address_line_1');
            }

            if (! Schema::hasColumn('tenants', 'state_province')) {
                $table->string('state_province', 120)->nullable()->after('address_line_2');
            }

            if (! Schema::hasColumn('tenants', 'city')) {
                $table->string('city', 120)->nullable()->after('state_province');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            foreach ([
                'city',
                'state_province',
                'address_line_2',
                'address_line_1',
                'postal_code',
                'google_place_id',
                'search_location',
                'country_code',
                'country',
            ] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
