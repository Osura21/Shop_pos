<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ThemeSeeder::class,
            SuperAdminPermissionSeeder::class,
            SuperAdminSeeder::class,
            SystemPermissionSeeder::class,
            PermissionCleanupSeeder::class,
            // VendorSubscriptionPlanSeeder::class,
            DistrictCitySeeder::class,
            // LoyaltySeeder::class,

        ]);
    }
}
