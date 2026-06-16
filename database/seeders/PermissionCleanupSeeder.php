<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionCleanupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // OR delete full vendor dashboard set
        Permission::whereIn('name', [
            'dashboard-revenue.view',
            'dashboard-daily-orders.view',
            'dashboard-recent-orders.view',
            'dashboard-shortcuts.view',
            'dashboard-top-products.view',
            'dashboard-order-status.view',
            'dashboard-order-types.view',
        ])->delete();
    }
}
