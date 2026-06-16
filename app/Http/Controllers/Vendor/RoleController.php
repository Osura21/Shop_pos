<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->where('guard_name', 'vendor')
            ->where('tenant_id', $this->tenantId())
            ->with('permissions:id,name,section_name')
            ->latest()
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->map(fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'section_name' => $permission->section_name,
                    ])->values(),
                    'permissions_count' => $role->permissions->count(),
                    'created_at' => optional($role->created_at)?->format('Y-m-d h:i A'),
                    'updated_at' => optional($role->updated_at)?->format('Y-m-d h:i A'),
                ];
            });

        return Inertia::render('VendorAdmin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Roles/CreateUpdate', [
            'role' => null,
            'permissionSections' => $this->permissionSections(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        $allowedPermissions = $this->allowedPermissionNames();
        $selectedPermissions = array_values(array_intersect($data['permissions'] ?? [], $allowedPermissions));

        DB::transaction(function () use ($data, $selectedPermissions) {
            $role = Role::query()->create([
                'name' => $data['name'],
                'guard_name' => 'vendor',
                'tenant_id' => $this->tenantId(),
            ]);

            // Flush Spatie's permission cache so the new role is recognized
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            $role->syncPermissions($selectedPermissions);
        });

        return redirect()
            ->route('vendor.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit($id)
    {
        $role = $this->findTenantRole($id);
        $role->load('permissions:id,name,section_name');

        return Inertia::render('VendorAdmin/Roles/CreateUpdate', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'section_name' => $permission->section_name,
                ])->values(),
            ],
            'permissionSections' => $this->permissionSections(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $role = $this->findTenantRole($id);

        $data = $request->validate($this->rules($role->id), $this->messages());

        $allowedPermissions = $this->allowedPermissionNames();
        $selectedPermissions = array_values(array_intersect($data['permissions'] ?? [], $allowedPermissions));

        DB::transaction(function () use ($role, $data, $selectedPermissions) {
            $role->update([
                'name' => $data['name'],
            ]);

            $role->syncPermissions($selectedPermissions);
        });

        return redirect()
            ->route('vendor.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy($id)
    {
        $role = $this->findTenantRole($id);

        $isAssigned = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->exists();

        if ($isAssigned) {
            return back()->withErrors([
                'general' => 'This role cannot be deleted because it is already assigned to one or more users.',
            ]);
        }

        $role->delete();

        return redirect()
            ->route('vendor.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    private function findTenantRole($id): Role
    {
        return Role::query()
            ->where('guard_name', 'vendor')
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);
    }

    private function permissionSections(): array
    {
        $allowed = $this->allowedPermissionNames();

        return Permission::query()
            ->where('guard_name', 'vendor')
            ->whereIn('name', $allowed)
            ->orderBy('section_name')
            ->orderBy('name')
            ->get(['id', 'name', 'section_name'])
            ->groupBy('section_name')
            ->map(function ($permissions, $section) {
                return [
                    'section' => $section,
                    'label' => Str::of($section)->replace('-', ' ')->title()->value(),
                    'permissions' => $permissions->map(fn ($permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function allowedPermissionNames(): array
    {
        return $this->currentUser()
            ? $this->currentUser()
                ->getAllPermissions()
                ->where('guard_name', 'vendor')
                ->pluck('name')
                ->values()
                ->all()
            : [];
    }

    private function currentUser()
    {
        return auth('vendor')->user() ?? auth()->user();
    }

    private function tenantId(): int
    {
        return (int) (tenant()->id ?? optional($this->currentUser())->tenant_id);
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query
                        ->where('guard_name', 'vendor')
                        ->where('tenant_id', $this->tenantId()))
                    ->ignore($ignoreId),
            ],
            'permissions' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    $value = $value ?? [];

                    $modules = [
                        'branches' => 'Branches',
                        'users' => 'Users',
                        'customers' => 'Customers',
                        'menus' => 'Menus',
                        'online-menus' => 'Online Menus',
                        'categories' => 'Categories',
                        'units' => 'Units',
                        'suppliers' => 'Suppliers',
                        'ingredients' => 'Ingredients',
                        'stock-movements' => 'Stock Movements',
                        'purchases' => 'Purchases',
                        'products' => 'Products',
                        'options' => 'Options',
                        'taxes' => 'Taxes',
                        'floors' => 'Floors',
                        'zones' => 'Zones',
                        'tables' => 'Tables',
                        'table-merges' => 'Table Merges',
                        'pms' => 'PMS',
                        'settings-mail' => 'Mail Settings',
                        'pos-registers' => 'POS Registers',
                        'pos-sessions' => 'POS Sessions',
                        'pos-cash-movements' => 'POS Cash Movements',
                        'pos-kitchen' => 'POS Kitchen',
                        'sales-reasons' => 'Sales Reasons',
                        'gift-cards' => 'Gift Cards',
                        'gift-card-batches' => 'Gift Card Batches',
                        'promotion-discounts' => 'Promotion Discounts',
                        'promotion-vouchers' => 'Promotion Vouchers',
                        'loyalty-programs' => 'Loyalty Programs',
                        'loyalty-tiers' => 'Loyalty Tiers',
                        'loyalty-rewards' => 'Loyalty Rewards',
                        'loyalty-promotions' => 'Loyalty Promotions',
                        'roles-permissions' => 'Roles Permissions',
                        'stock-movements' => 'Stock Movements',
                    ];

                    foreach ($modules as $prefix => $label) {
                        $hasAction = in_array("{$prefix}.create", $value) ||
                                     in_array("{$prefix}.edit", $value) ||
                                     in_array("{$prefix}.delete", $value);

                        if ($hasAction && ! in_array("{$prefix}.view", $value)) {
                            $fail("The {$label} View permission is required when Create, Edit, or Delete permission is selected.");
                        }
                    }

                    $hasStockMovements = in_array('stock-movements.view', $value) ||
                                         in_array('stock-movements.create', $value) ||
                                         in_array('stock-movements.edit', $value) ||
                                         in_array('stock-movements.delete', $value);

                    if ($hasStockMovements && ! in_array('inventory.view', $value)) {
                        $fail("The Inventory View permission is required when any Stock Movements permission is selected.");
                    }
                },
            ],
            'permissions.*' => ['string'],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'This role name already exists.',
            'name.max' => 'Role name may not be greater than 255 characters.',
        ];
    }
}
