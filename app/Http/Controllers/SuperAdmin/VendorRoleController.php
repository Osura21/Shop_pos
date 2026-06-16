<?php

namespace App\Http\Controllers\SuperAdmin;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class VendorRoleController
{
    public function create()
    {
        return Inertia::render('SuperAdmin/VendorRoles/Create', [
            'permissions' => Permission::where('guard_name', 'vendor')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'vendor',
            'tenant_id' => null,
        ]);

        $role->syncPermissions($request->permissions ?? []);
        return redirect()->route('vendor.roles.index');
    }
}


