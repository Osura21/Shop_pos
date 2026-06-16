<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_subscription_plan_id')->nullable()->after('status');
            $table->string('vendor_subscription_status')->default('active')->after('vendor_subscription_plan_id');
            $table->boolean('vendor_panel_enabled')->default(true)->after('vendor_subscription_status');
            $table->timestamp('vendor_subscription_started_at')->nullable()->after('vendor_panel_enabled');
            $table->timestamp('vendor_subscription_ends_at')->nullable()->after('vendor_subscription_started_at');
            $table->timestamp('vendor_trial_ends_at')->nullable()->after('vendor_subscription_ends_at');

            $table->foreign('vendor_subscription_plan_id', 'tenants_vsp_id_foreign')
                ->references('id')
                ->on('vendor_subscription_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign('tenants_vsp_id_foreign');
            $table->dropColumn('vendor_subscription_plan_id');
            $table->dropColumn([
                'vendor_subscription_status',
                'vendor_panel_enabled',
                'vendor_subscription_started_at',
                'vendor_subscription_ends_at',
                'vendor_trial_ends_at',
            ]);
        });
    }
};
