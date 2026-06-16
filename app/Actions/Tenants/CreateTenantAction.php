<?php

namespace App\Actions\Tenants;

use App\Models\Domain;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateTenantAction
{
    public function execute(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {

            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'theme_id' => $data['theme_id'],
                'status' => 'active',
            ]);

            Domain::create([
                'tenant_id' => $tenant->id,
                'domain' => $data['domain'],
                'is_primary' => true,
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
            ]);

            $role = Role::where('guard_name', 'vendor')
                ->whereNull('tenant_id')
                ->firstOrFail();

            $user->assignRole($role);

            return $tenant;
        });
    }
}
