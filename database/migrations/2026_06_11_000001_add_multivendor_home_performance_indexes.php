<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'tenants_status_created_at_idx');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_primary', 'id'], 'domains_tenant_primary_id_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index(['food_category_id', 'is_active', 'parent_id', 'tenant_id'], 'categories_food_active_parent_tenant_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'products_active_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_created_at_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_food_active_parent_tenant_idx');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex('domains_tenant_primary_id_idx');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('tenants_status_created_at_idx');
        });
    }
};
