<?php

namespace App\Http\Controllers\SuperAdmin;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperRoleController
{
    public function create()
    {
        return Inertia::render('SuperAdmin/Roles/Create', [
            'permissions' => Permission::where('guard_name', 'superadmin')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'superadmin',
            'tenant_id' => null,
        ]);

        $role->syncPermissions($request->permissions ?? []);
        return redirect()->route('super.roles.index');
    }
}

