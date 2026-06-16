<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController
{
    public function index()
    {
        $roles = Role::query()
            ->whereNull('tenant_id')
            ->with('permissions:id,name,guard_name')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name', 'tenant_id']);

        return Inertia::render('SuperAdmin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/Roles/CreateUpdate', [
            'role' => null,
            'superPermissions' => Permission::where('guard_name', 'superadmin')->select(['id', 'name', 'guard_name'])->orderBy('name')->get(),
            'vendorPermissions' => Permission::where('guard_name', 'vendor')->select(['id', 'name', 'guard_name'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:roles,name'],
            'guard_name' => ['required', 'in:superadmin,vendor'],
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
                        'pos-registers' => 'POS Registers',
                        'pos-sessions' => 'POS Sessions',
                        'pos-cash-movements' => 'POS Cash Movements',
                        'sales-reasons' => 'Sales Reasons',
                        'gift-cards' => 'Gift Cards',
                        'gift-card-batches' => 'Gift Card Batches',
                        'promotion-discounts' => 'Promotion Discounts',
                        'promotion-vouchers' => 'Promotion Vouchers',
                        'loyalty-programs' => 'Loyalty Programs',
                        'loyalty-tiers' => 'Loyalty Tiers',
                        'loyalty-rewards' => 'Loyalty Rewards',
                        'loyalty-promotions' => 'Loyalty Promotions',
                        'seo-footer-links' => 'SEO Footer Links',
                        'roles-permissions' => 'Roles Permissions',
                        'mail-settings' => 'Mail Settings',
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
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'],
            'tenant_id' => null,
        ]);

        $validPermissions = Permission::query()
            ->where('guard_name', $data['guard_name'])
            ->whereIn('name', $data['permissions'] ?? [])
            ->pluck('name')
            ->toArray();

        $role->syncPermissions($validPermissions);

        return redirect()
            ->route('settings.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        abort_if($role->tenant_id !== null, 403);

        return Inertia::render('SuperAdmin/Roles/CreateUpdate', [
            'role' => $role->load('permissions:id,name,guard_name'),
            'superPermissions' => Permission::where('guard_name', 'superadmin')->select(['id', 'name', 'guard_name'])->orderBy('name')->get(),
            'vendorPermissions' => Permission::where('guard_name', 'vendor')->select(['id', 'name', 'guard_name'])->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->tenant_id !== null, 403);

        $data = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:150'
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
                        'pos-registers' => 'POS Registers',
                        'pos-sessions' => 'POS Sessions',
                        'pos-cash-movements' => 'POS Cash Movements',
                        'sales-reasons' => 'Sales Reasons',
                        'gift-cards' => 'Gift Cards',
                        'gift-card-batches' => 'Gift Card Batches',
                        'promotion-discounts' => 'Promotion Discounts',
                        'promotion-vouchers' => 'Promotion Vouchers',
                        'loyalty-programs' => 'Loyalty Programs',
                        'loyalty-tiers' => 'Loyalty Tiers',
                        'loyalty-rewards' => 'Loyalty Rewards',
                        'loyalty-promotions' => 'Loyalty Promotions',
                        'seo-footer-links' => 'SEO Footer Links',
                        'roles-permissions' => 'Roles Permissions',
                        'mail-settings' => 'Mail Settings',
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
                        $fail('The Inventory View permission is required when any Stock Movements permission is selected.');
                    }
                },
            ],
            'permissions.*' => ['string'],
        ]);

        $validPermissions = Permission::query()
            ->where('guard_name', $role->guard_name)
            ->whereIn('name', $data['permissions'] ?? [])
            ->pluck('name')
            ->toArray();

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($validPermissions);

        return redirect()
            ->route('settings.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['Super Admin', 'Vendor Admin'])) {
            return redirect()->back()->with('error', 'This role cannot be deleted.');
        }

        abort_if($role->tenant_id !== null, 403);

        $role->delete();

        return redirect()
            ->route('settings.roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
