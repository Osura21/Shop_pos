<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'superadmin',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // assign ALL superadmin permissions
        $role->syncPermissions(
            Permission::where('guard_name', 'superadmin')->pluck('name')
        );

        $user = User::firstOrCreate(
            ['email' => 'superadmin@pos.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'tenant_id' => null,
            ]
        );

        $user->assignRole($role);
    }
}
