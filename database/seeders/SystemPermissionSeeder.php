<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Vendor capability permissions (FOR VENDOR PANEL)
        $vendorPermissions = [
            ['section_name' => 'roles-permissions', 'name' => 'roles-permissions.view'],
            ['section_name' => 'roles-permissions', 'name' => 'roles-permissions.create'],
            ['section_name' => 'roles-permissions', 'name' => 'roles-permissions.edit'],
            ['section_name' => 'roles-permissions', 'name' => 'roles-permissions.delete'],

            ['section_name' => 'dashboard', 'name' => 'dashboard.view'],
            ['section_name' => 'dashboard', 'name' => 'dashboard.revenue.view'],
            ['section_name' => 'dashboard', 'name' => 'dashboard.daily-orders.view'],
            ['section_name' => 'dashboard', 'name' => 'dashboard.recent-orders.view'],
            ['section_name' => 'dashboard', 'name' => 'dashboard.shortcuts.view'],
            ['section_name' => 'dashboard', 'name' => 'dashboard.top-products.view'],
            ['section_name' => 'dashboard', 'name' => 'dashboard.order-status.view'],
            ['section_name' => 'dashboard', 'name' => 'dashboard.order-types.view'],

            ['section_name' => 'branches', 'name' => 'branches.view'],
            ['section_name' => 'branches', 'name' => 'branches.create'],
            ['section_name' => 'branches', 'name' => 'branches.edit'],
            ['section_name' => 'branches', 'name' => 'branches.delete'],

            ['section_name' => 'users', 'name' => 'users.view'],
            ['section_name' => 'users', 'name' => 'users.create'],
            ['section_name' => 'users', 'name' => 'users.edit'],
            ['section_name' => 'users', 'name' => 'users.delete'],

            ['section_name' => 'customers', 'name' => 'customers.view'],
            ['section_name' => 'customers', 'name' => 'customers.create'],
            ['section_name' => 'customers', 'name' => 'customers.edit'],
            ['section_name' => 'customers', 'name' => 'customers.delete'],

            ['section_name' => 'menus', 'name' => 'menus.view'],
            ['section_name' => 'menus', 'name' => 'menus.create'],
            ['section_name' => 'menus', 'name' => 'menus.edit'],
            ['section_name' => 'menus', 'name' => 'menus.delete'],

            ['section_name' => 'online-menus', 'name' => 'online-menus.view'],
            ['section_name' => 'online-menus', 'name' => 'online-menus.create'],
            ['section_name' => 'online-menus', 'name' => 'online-menus.edit'],
            ['section_name' => 'online-menus', 'name' => 'online-menus.delete'],

            ['section_name' => 'categories', 'name' => 'categories.view'],
            ['section_name' => 'categories', 'name' => 'categories.create'],
            ['section_name' => 'categories', 'name' => 'categories.edit'],
            ['section_name' => 'categories', 'name' => 'categories.delete'],

            ['section_name' => 'inventory', 'name' => 'inventory.view'],

            ['section_name' => 'units', 'name' => 'units.view'],
            ['section_name' => 'units', 'name' => 'units.create'],
            ['section_name' => 'units', 'name' => 'units.edit'],
            ['section_name' => 'units', 'name' => 'units.delete'],

            ['section_name' => 'suppliers', 'name' => 'suppliers.view'],
            ['section_name' => 'suppliers', 'name' => 'suppliers.create'],
            ['section_name' => 'suppliers', 'name' => 'suppliers.edit'],
            ['section_name' => 'suppliers', 'name' => 'suppliers.delete'],

            ['section_name' => 'ingredients', 'name' => 'ingredients.view'],
            ['section_name' => 'ingredients', 'name' => 'ingredients.create'],
            ['section_name' => 'ingredients', 'name' => 'ingredients.edit'],
            ['section_name' => 'ingredients', 'name' => 'ingredients.delete'],

            ['section_name' => 'stock-movements', 'name' => 'stock-movements.view'],
            ['section_name' => 'stock-movements', 'name' => 'stock-movements.create'],
            ['section_name' => 'stock-movements', 'name' => 'stock-movements.edit'],
            ['section_name' => 'stock-movements', 'name' => 'stock-movements.delete'],

            ['section_name' => 'purchases', 'name' => 'purchases.view'],
            ['section_name' => 'purchases', 'name' => 'purchases.create'],
            ['section_name' => 'purchases', 'name' => 'purchases.edit'],
            ['section_name' => 'purchases', 'name' => 'purchases.delete'],

            ['section_name' => 'inventory-analytics', 'name' => 'inventory-analytics.view'],

            ['section_name' => 'settings', 'name' => 'settings.view'],
            ['section_name' => 'settings-currency', 'name' => 'settings-currency.view'],
            ['section_name' => 'settings-kitchen-alert', 'name' => 'settings-kitchen-alert.view'],
            ['section_name' => 'settings', 'name' => 'settings.logo.view'],
            ['section_name' => 'settings-mail', 'name' => 'settings-mail.view'],
            ['section_name' => 'settings-mail', 'name' => 'settings-mail.edit'],

            ['section_name' => 'products', 'name' => 'products.view'],
            ['section_name' => 'products', 'name' => 'products.create'],
            ['section_name' => 'products', 'name' => 'products.edit'],
            ['section_name' => 'products', 'name' => 'products.delete'],

            ['section_name' => 'options', 'name' => 'options.view'],
            ['section_name' => 'options', 'name' => 'options.create'],
            ['section_name' => 'options', 'name' => 'options.edit'],
            ['section_name' => 'options', 'name' => 'options.delete'],

            ['section_name' => 'taxes', 'name' => 'taxes.view'],
            ['section_name' => 'taxes', 'name' => 'taxes.create'],
            ['section_name' => 'taxes', 'name' => 'taxes.edit'],
            ['section_name' => 'taxes', 'name' => 'taxes.delete'],

            ['section_name' => 'seating-plan', 'name' => 'seating-plan.view'],

            ['section_name' => 'floors', 'name' => 'floors.view'],
            ['section_name' => 'floors', 'name' => 'floors.create'],
            ['section_name' => 'floors', 'name' => 'floors.edit'],
            ['section_name' => 'floors', 'name' => 'floors.delete'],

            ['section_name' => 'zones', 'name' => 'zones.view'],
            ['section_name' => 'zones', 'name' => 'zones.create'],
            ['section_name' => 'zones', 'name' => 'zones.edit'],
            ['section_name' => 'zones', 'name' => 'zones.delete'],

            ['section_name' => 'tables', 'name' => 'tables.view'],
            ['section_name' => 'tables', 'name' => 'tables.create'],
            ['section_name' => 'tables', 'name' => 'tables.edit'],
            ['section_name' => 'tables', 'name' => 'tables.delete'],

            ['section_name' => 'table-merges', 'name' => 'table-merges.view'],
            ['section_name' => 'table-merges', 'name' => 'table-merges.create'],
            ['section_name' => 'table-merges', 'name' => 'table-merges.edit'],
            ['section_name' => 'table-merges', 'name' => 'table-merges.delete'],

            ['section_name' => 'pos', 'name' => 'pos.view'],
            ['section_name' => 'pms', 'name' => 'pms.view'],
            ['section_name' => 'pms', 'name' => 'pms.create'],
            ['section_name' => 'pms', 'name' => 'pms.edit'],
            ['section_name' => 'pms', 'name' => 'pms.delete'],
            ['section_name' => 'pos-registers', 'name' => 'pos-registers.view'],
            ['section_name' => 'pos-registers', 'name' => 'pos-registers.create'],
            ['section_name' => 'pos-registers', 'name' => 'pos-registers.edit'],
            ['section_name' => 'pos-registers', 'name' => 'pos-registers.delete'],

            ['section_name' => 'pos-sessions', 'name' => 'pos-sessions.view'],
            ['section_name' => 'pos-sessions', 'name' => 'pos-sessions.create'],
            ['section_name' => 'pos-sessions', 'name' => 'pos-sessions.edit'],
            ['section_name' => 'pos-sessions', 'name' => 'pos-sessions.delete'],

            ['section_name' => 'pos-cash-movements', 'name' => 'pos-cash-movements.view'],
            ['section_name' => 'pos-cash-movements', 'name' => 'pos-cash-movements.create'],
            ['section_name' => 'pos-cash-movements', 'name' => 'pos-cash-movements.edit'],
            ['section_name' => 'pos-cash-movements', 'name' => 'pos-cash-movements.delete'],

            ['section_name' => 'pos-kitchen', 'name' => 'pos-kitchen.view'],
            ['section_name' => 'pos-kitchen', 'name' => 'pos-kitchen.edit'],

            ['section_name' => 'sales', 'name' => 'sales.view'],

            ['section_name' => 'sales-orders', 'name' => 'sales-orders.view'],
            ['section_name' => 'sales-invoices', 'name' => 'sales-invoices.view'],
            ['section_name' => 'sales-payments', 'name' => 'sales-payments.view'],
            ['section_name' => 'sales-reasons', 'name' => 'sales-reasons.view'],
            ['section_name' => 'sales-reasons', 'name' => 'sales-reasons.create'],
            ['section_name' => 'sales-reasons', 'name' => 'sales-reasons.edit'],
            ['section_name' => 'sales-reasons', 'name' => 'sales-reasons.delete'],
            ['section_name' => 'gift-card-analytics', 'name' => 'gift-card-analytics.view'],
            ['section_name' => 'gift-cards', 'name' => 'gift-cards.view'],
            ['section_name' => 'gift-cards', 'name' => 'gift-cards.create'],
            ['section_name' => 'gift-cards', 'name' => 'gift-cards.edit'],
            ['section_name' => 'gift-cards', 'name' => 'gift-cards.delete'],
            ['section_name' => 'gift-card-transactions', 'name' => 'gift-card-transactions.view'],
            ['section_name' => 'gift-card-batches', 'name' => 'gift-card-batches.view'],
            ['section_name' => 'gift-card-batches', 'name' => 'gift-card-batches.create'],
            ['section_name' => 'gift-card-batches', 'name' => 'gift-card-batches.edit'],
            ['section_name' => 'gift-card-batches', 'name' => 'gift-card-batches.delete'],
            ['section_name' => 'promotions', 'name' => 'promotions.view'],
            ['section_name' => 'promotion-discounts', 'name' => 'promotion-discounts.view'],
            ['section_name' => 'promotion-discounts', 'name' => 'promotion-discounts.create'],
            ['section_name' => 'promotion-discounts', 'name' => 'promotion-discounts.edit'],
            ['section_name' => 'promotion-discounts', 'name' => 'promotion-discounts.delete'],
            ['section_name' => 'promotion-vouchers', 'name' => 'promotion-vouchers.view'],
            ['section_name' => 'promotion-vouchers', 'name' => 'promotion-vouchers.create'],
            ['section_name' => 'promotion-vouchers', 'name' => 'promotion-vouchers.edit'],
            ['section_name' => 'promotion-vouchers', 'name' => 'promotion-vouchers.delete'],
            ['section_name' => 'loyalty', 'name' => 'loyalty.view'],
            ['section_name' => 'loyalty-programs', 'name' => 'loyalty-programs.view'],
            ['section_name' => 'loyalty-programs', 'name' => 'loyalty-programs.create'],
            ['section_name' => 'loyalty-programs', 'name' => 'loyalty-programs.edit'],
            ['section_name' => 'loyalty-programs', 'name' => 'loyalty-programs.delete'],
            ['section_name' => 'loyalty-tiers', 'name' => 'loyalty-tiers.view'],
            ['section_name' => 'loyalty-tiers', 'name' => 'loyalty-tiers.create'],
            ['section_name' => 'loyalty-tiers', 'name' => 'loyalty-tiers.edit'],
            ['section_name' => 'loyalty-tiers', 'name' => 'loyalty-tiers.delete'],
            ['section_name' => 'loyalty-rewards', 'name' => 'loyalty-rewards.view'],
            ['section_name' => 'loyalty-rewards', 'name' => 'loyalty-rewards.create'],
            ['section_name' => 'loyalty-rewards', 'name' => 'loyalty-rewards.edit'],
            ['section_name' => 'loyalty-rewards', 'name' => 'loyalty-rewards.delete'],
            ['section_name' => 'loyalty-promotions', 'name' => 'loyalty-promotions.view'],
            ['section_name' => 'loyalty-promotions', 'name' => 'loyalty-promotions.create'],
            ['section_name' => 'loyalty-promotions', 'name' => 'loyalty-promotions.edit'],
            ['section_name' => 'loyalty-promotions', 'name' => 'loyalty-promotions.delete'],
            ['section_name' => 'loyalty-customers', 'name' => 'loyalty-customers.view'],
            ['section_name' => 'loyalty-gifts', 'name' => 'loyalty-gifts.view'],
            ['section_name' => 'loyalty-transactions', 'name' => 'loyalty-transactions.view'],


        ];

        $reportPermissions = [
    ['section_name' => 'reports', 'name' => 'reports.view'],

    // Restaurant Sales Reports
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.restaurant-sales.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.sales.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.upcoming-orders.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.products-purchase.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.sales-by-creator.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.sales-by-cashier.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.tax.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.product-tax.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.branch-performance.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.payments.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.cost-revenue-order.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.cost-revenue-product.view'],
    ['section_name' => 'reports-restaurant-sales', 'name' => 'reports.discounts-vouchers.view'],

    // POS Reports
    ['section_name' => 'reports-pos', 'name' => 'reports.pos.view'],
    ['section_name' => 'reports-pos', 'name' => 'reports.register-summary.view'],
    ['section_name' => 'reports-pos', 'name' => 'reports.cash-movement.view'],

    // Gift Card Reports
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card.view'],
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card-sales.view'],
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card-redemption.view'],
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card-outstanding-balance.view'],
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card-liability.view'],
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card-expiry.view'],
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card-transactions.view'],
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card-branch-performance.view'],
    ['section_name' => 'reports-gift-card', 'name' => 'reports.gift-card-batch.view'],

    // Inventory Reports
    ['section_name' => 'reports-inventory', 'name' => 'reports.inventory.view'],
    ['section_name' => 'reports-inventory', 'name' => 'reports.ingredient-usage.view'],
    ['section_name' => 'reports-inventory', 'name' => 'reports.low-stock-alerts.view'],

    // Loyalty Overview Reports
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.loyalty-overview.view'],
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.loyalty-program-summary.view'],
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.total-earned-points.view'],
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.total-redeemed-points.view'],
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.total-expired-points.view'],
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.system-points-balance.view'],
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.redemption-rate.view'],
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.average-points-program.view'],
    ['section_name' => 'reports-loyalty-overview', 'name' => 'reports.points-lifecycle-timeline.view'],

    // Loyalty Customer Reports
    ['section_name' => 'reports-loyalty-customers', 'name' => 'reports.loyalty-customers.view'],
    ['section_name' => 'reports-loyalty-customers', 'name' => 'reports.last-loyalty-activity.view'],
    ['section_name' => 'reports-loyalty-customers', 'name' => 'reports.inactive-loyalty-customers.view'],
    ['section_name' => 'reports-loyalty-customers', 'name' => 'reports.customers-no-redemptions.view'],
    ['section_name' => 'reports-loyalty-customers', 'name' => 'reports.top-customers-points.view'],

    // Loyalty Tier Reports
    ['section_name' => 'reports-loyalty-tiers', 'name' => 'reports.loyalty-tiers.view'],
    ['section_name' => 'reports-loyalty-tiers', 'name' => 'reports.customers-tier-distribution.view'],
    ['section_name' => 'reports-loyalty-tiers', 'name' => 'reports.redemption-rate-tier.view'],

    // Loyalty Rewards Reports
    ['section_name' => 'reports-loyalty-rewards', 'name' => 'reports.loyalty-rewards.view'],
    ['section_name' => 'reports-loyalty-rewards', 'name' => 'reports.most-redeemed-rewards.view'],
    ['section_name' => 'reports-loyalty-rewards', 'name' => 'reports.least-used-rewards.view'],
    ['section_name' => 'reports-loyalty-rewards', 'name' => 'reports.never-redeemed-rewards.view'],
    ['section_name' => 'reports-loyalty-rewards', 'name' => 'reports.rewards-by-type.view'],
    ['section_name' => 'reports-loyalty-rewards', 'name' => 'reports.rewards-by-tier.view'],
    ['section_name' => 'reports-loyalty-rewards', 'name' => 'reports.rewards-by-program.view'],

    // Loyalty Gifts Reports
    ['section_name' => 'reports-loyalty-gifts', 'name' => 'reports.loyalty-gifts.view'],
    ['section_name' => 'reports-loyalty-gifts', 'name' => 'reports.available-gifts.view'],
    ['section_name' => 'reports-loyalty-gifts', 'name' => 'reports.used-gifts.view'],
    ['section_name' => 'reports-loyalty-gifts', 'name' => 'reports.expired-gifts.view'],
    ['section_name' => 'reports-loyalty-gifts', 'name' => 'reports.gift-usage-rate.view'],
    ['section_name' => 'reports-loyalty-gifts', 'name' => 'reports.unused-gifts-customer.view'],

    // Loyalty Redemptions Reports
    ['section_name' => 'reports-loyalty-redemptions', 'name' => 'reports.loyalty-redemptions.view'],
    ['section_name' => 'reports-loyalty-redemptions', 'name' => 'reports.redemptions-by-status.view'],
    ['section_name' => 'reports-loyalty-redemptions', 'name' => 'reports.redemptions-by-program.view'],
    ['section_name' => 'reports-loyalty-redemptions', 'name' => 'reports.average-points-redemption.view'],

    // Loyalty Promotions Reports
    ['section_name' => 'reports-loyalty-promotions', 'name' => 'reports.loyalty-promotions.view'],
    ['section_name' => 'reports-loyalty-promotions', 'name' => 'reports.active-promotions.view'],
    ['section_name' => 'reports-loyalty-promotions', 'name' => 'reports.expired-promotions.view'],
    ['section_name' => 'reports-loyalty-promotions', 'name' => 'reports.promotion-usage.view'],
    ['section_name' => 'reports-loyalty-promotions', 'name' => 'reports.highest-impact-promotions.view'],
    ['section_name' => 'reports-loyalty-promotions', 'name' => 'reports.bonus-vs-multiplier-comparison.view'],
    ['section_name' => 'reports-loyalty-promotions', 'name' => 'reports.category-boost-promotions.view'],
    ['section_name' => 'reports-loyalty-promotions', 'name' => 'reports.new-member-promotions.view'],

    // Loyalty Financial & ROI Reports
    ['section_name' => 'reports-loyalty-financial-roi', 'name' => 'reports.loyalty-financial-roi.view'],
    ['section_name' => 'reports-loyalty-financial-roi', 'name' => 'reports.loyalty-average-order-value.view'],
    ['section_name' => 'reports-loyalty-financial-roi', 'name' => 'reports.loyalty-customer-revenue.view'],
    ['section_name' => 'reports-loyalty-financial-roi', 'name' => 'reports.revenue-before-after-loyalty.view'],
    ['section_name' => 'reports-loyalty-financial-roi', 'name' => 'reports.free-items-cost.view'],

    // System Reports
    ['section_name' => 'reports-system', 'name' => 'reports.system.view'],
    ['section_name' => 'reports-system', 'name' => 'reports.categorized-products.view'],

    ['section_name' => 'reports-export', 'name' => 'reports.export'],
['section_name' => 'reports-export', 'name' => 'reports.export.csv'],
['section_name' => 'reports-export', 'name' => 'reports.export.xlsx'],
['section_name' => 'reports-export', 'name' => 'reports.export.pdf'],
['section_name' => 'reports-export', 'name' => 'reports.export.json'],
];

$vendorPermissions = array_merge($vendorPermissions, $reportPermissions);

        $activityPermissions = [
            ['section_name' => 'activities', 'name' => 'activities.view'],
            ['section_name' => 'activity-logs', 'name' => 'activity-logs.view'],
            ['section_name' => 'authentication-logs', 'name' => 'authentication-logs.view'],
        ];

        $vendorPermissions = array_merge($vendorPermissions, $activityPermissions);

        foreach ($vendorPermissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'vendor',
                ],
                [
                    'section_name' => $permission['section_name'],
                ]
            );
        }

        // 🔹 Default system-level vendor role
        $vendorAdminRole = Role::firstOrCreate([
            'name' => 'Vendor Admin',
            'guard_name' => 'vendor',
        ]);

        $vendorAdminRole->givePermissionTo(
            collect($activityPermissions)
                ->pluck('name')
                ->merge(['settings-mail.view', 'settings-mail.edit'])
                ->all()
        );
    }
}
