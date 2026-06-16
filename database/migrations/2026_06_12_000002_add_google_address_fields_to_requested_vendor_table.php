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
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('requested_vendor')) {
            return;
        }

        Schema::table('requested_vendor', function (Blueprint $table) {
            foreach ([
                'state_province',
                'address_line_2',
                'address_line_1',
                'postal_code',
                'google_place_id',
                'search_location',
                'country_code',
            ] as $column) {
                if (Schema::hasColumn('requested_vendor', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
