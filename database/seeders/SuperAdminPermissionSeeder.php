<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SuperAdminPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['section_name' => 'vendor', 'name' => 'vendor.view'],
            ['section_name' => 'vendor', 'name' => 'vendor.create'],
            ['section_name' => 'vendor', 'name' => 'vendor.edit'],
            ['section_name' => 'vendor', 'name' => 'vendor.delete'],

            ['section_name' => 'food-types', 'name' => 'food-types.view'],
            ['section_name' => 'food-types', 'name' => 'food-types.create'],
            ['section_name' => 'food-types', 'name' => 'food-types.edit'],
            ['section_name' => 'food-types', 'name' => 'food-types.delete'],

            ['section_name' => 'seo-footer-links', 'name' => 'seo-footer-links.view'],
            ['section_name' => 'seo-footer-links', 'name' => 'seo-footer-links.create'],
            ['section_name' => 'seo-footer-links', 'name' => 'seo-footer-links.edit'],
            ['section_name' => 'seo-footer-links', 'name' => 'seo-footer-links.delete'],

            ['section_name' => 'vendor-subscriptions', 'name' => 'vendor-subscriptions.view'],
            ['section_name' => 'vendor-subscriptions', 'name' => 'vendor-subscriptions.create'],
            ['section_name' => 'vendor-subscriptions', 'name' => 'vendor-subscriptions.edit'],
            ['section_name' => 'vendor-subscriptions', 'name' => 'vendor-subscriptions.delete'],

            ['section_name' => 'roles-permissions', 'name' => 'roles-permissions.view'],
            ['section_name' => 'roles-permissions', 'name' => 'roles-permissions.create'],
            ['section_name' => 'roles-permissions', 'name' => 'roles-permissions.edit'],
            ['section_name' => 'roles-permissions', 'name' => 'roles-permissions.delete'],

            ['section_name' => 'vendor', 'name' => 'vendor.view'],
            ['section_name' => 'vendor', 'name' => 'vendor.create'],
            ['section_name' => 'vendor', 'name' => 'vendor.edit'],
            ['section_name' => 'vendor', 'name' => 'vendor.delete'],

            ['section_name' => 'staff-member', 'name' => 'staff-member.view'],
            ['section_name' => 'staff-member', 'name' => 'staff-member.create'],
            ['section_name' => 'staff-member', 'name' => 'staff-member.edit'],
            ['section_name' => 'staff-member', 'name' => 'staff-member.delete'],

            ['section_name' => 'mail-settings', 'name' => 'mail-settings.view'],
            ['section_name' => 'mail-settings', 'name' => 'mail-settings.edit'],


        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name'       => $permission['name'],
                    'guard_name' => 'superadmin',
                ],
                [
                    'section_name' => $permission['section_name'],
                ]
            );
        }
    }
}
