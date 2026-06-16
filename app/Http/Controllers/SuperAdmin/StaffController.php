<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class StaffController
{
    public function index()
    {
        return Inertia::render('SuperAdmin/StaffMembers/Index');
    }

    public function getData(Request $request)
    {
        $query = User::query()
            ->select([
                'id',
                'tenant_id',
                'name',
                'email',
                Schema::hasColumn('users', 'phone') ? 'phone' : 'email as phone',
                Schema::hasColumn('users', 'status') ? 'status' : 'email as status',
                'created_at',
            ])
            // ->whereNotNull('tenant_id')
            ->latest();

        return DataTables::of($query)
            ->addColumn('role', function ($u) {
                return $u->getRoleNames()->first() ?? '-';
            })
            ->editColumn('phone', fn($u) => Schema::hasColumn('users', 'phone') ? ($u->phone ?? '') : '')
            ->editColumn('status', function ($u) {
                if (!Schema::hasColumn('users', 'status')) return 'active';

                if (is_numeric($u->status)) return ((int)$u->status === 1) ? 'active' : 'inactive';

                return $u->status ?? 'active';
            })
            ->editColumn('created_at', fn($u) => optional($u->created_at)->format('Y-m-d H:i'))
            ->toJson();
    }

    public function create()
    {
        $roles = Role::query()
            ->whereNull('tenant_id')
            ->whereIn('guard_name', ['superadmin', 'vendor'])
            ->select(['id', 'name', 'guard_name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('SuperAdmin/StaffMembers/CreateUpdate', [
            'staff' => null,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer'],
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
            'role_id'   => ['nullable', 'integer', 'exists:roles,id'],
            'phone'     => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:0,1'],
        ]);

        $user = User::create([
            'tenant_id' => $data['tenant_id'] ?? null,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'status'    => $data['status'] ?? '1',
            'password'  => Hash::make($data['password']),
        ]);

        if (!Schema::hasColumn('users', 'status')) {
            unset($user->status);
        }

      $this->syncUserRole($user, $data['role_id'] ?? null);


        return redirect()->route('settings.staff.index')
            ->with('success', 'Staff created successfully.');
    }

    public function edit($id)
    {
        $staff = User::query()
            // ->whereNotNull('tenant_id')
            ->findOrFail($id);

        $roles = Role::query()
            // ->whereNull('tenant_id')
            ->whereIn('guard_name', ['superadmin', 'vendor'])
            ->select(['id', 'name', 'guard_name'])
            ->orderBy('name')
            ->get();

$currentRoleId = $staff->roles()->value('id');

        return Inertia::render('SuperAdmin/StaffMembers/CreateUpdate', [
            'staff' => [
                'id'        => $staff->id,
                'tenant_id' => $staff->tenant_id,
                'name'      => $staff->name,
                'phone'     => $staff->phone,
                'email'     => $staff->email,
                'status'    => $staff->status ?? '1',
                'role_id'   => $currentRoleId,
            ],
            'roles' => $roles,
        ]);
    }
public function update(Request $request, $id)
{
    $staff = User::findOrFail($id);

    $data = $request->validate([
        'tenant_id' => ['nullable', 'integer'],
        'name'      => ['required', 'string', 'max:255'],
'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $staff->id],
'status' => ['nullable', 'in:0,1'],
        'password'  => ['nullable', 'string', 'min:6', 'confirmed'],
        'role_id'   => ['nullable', 'integer', 'exists:roles,id'],
        'phone'     => ['nullable', 'string', 'max:50'],
    ]);

    if (array_key_exists('tenant_id', $data)) {
        $staff->tenant_id = $data['tenant_id'] ?? $staff->tenant_id;
    }

    $staff->name  = $data['name'];
    $staff->email = $data['email'];

    if (Schema::hasColumn('users', 'phone')) {
        $staff->phone = $data['phone'] ?? null;
    }

    if (Schema::hasColumn('users', 'status')) {
        $staff->status = isset($data['status']) ? (string)$data['status'] : $staff->status;
    }

    if (!empty($data['password'])) {
        $staff->password = Hash::make($data['password']);
    }

    $staff->save();

    $this->syncUserRole($staff, $data['role_id'] ?? null);

    return redirect()->route('settings.staff.index')->with('success', 'Staff updated successfully.');
}
    public function destroy($id)
    {
        $staff = User::query()->findOrFail($id);
        $staff->delete();

        return redirect()->route('settings.staff.index')
            ->with('success', 'Staff deleted successfully.');
    }

 private function syncUserRole(User $user, $roleId): void
{
    if (empty($roleId)) {
        $user->syncRoles([]); // remove roles
        return;
    }

    $role = Role::query()->select('id', 'name', 'guard_name')->find($roleId);
    if (!$role) return;

    $user->guard_name = $role->guard_name;

    $user->syncRoles([$role]);
}


}
