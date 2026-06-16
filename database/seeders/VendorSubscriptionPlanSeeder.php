<?php

namespace Database\Seeders;

use App\Models\VendorSubscriptionPlan;
use Illuminate\Database\Seeder;

class VendorSubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'plan_name' => 'Starter POS',
                'plan_code' => 'VSP-STARTER',
                'short_description' => 'For single-location restaurants starting with core POS, menu setup, and basic sales visibility.',
                'status' => 'active',
                'is_default' => true,
                'monthly_price' => 0,
                'yearly_price' => 0,
                'currency_code' => 'LKR',
                'trial_days' => 0,
                'auto_renew' => true,
                'display_order' => 1,
                'badge' => 'Starter',
                'highlight_plan' => false,
                'most_popular' => false,
                'plan_card_color' => '#16A34A',
                'icon_key' => 'bi-shop',
                'features' => [
                    'products_count' => ['enabled' => true, 'limit_value' => 100],
                    'vendor_panel' => ['enabled' => true],
                    'reports' => ['enabled' => true],
                    'kitchen' => ['enabled' => true],
                ],
            ],
            [
                'plan_name' => 'Growth POS',
                'plan_code' => 'VSP-GROWTH',
                'short_description' => 'For restaurants that need loyalty, gift cards, promotions, seating, kitchen, and deeper reporting.',
                'status' => 'active',
                'is_default' => false,
                'monthly_price' => 5000,
                'yearly_price' => 51000,
                'yearly_discount_type' => 'percentage',
                'yearly_discount_value' => 15,
                'currency_code' => 'LKR',
                'trial_days' => 14,
                'auto_renew' => true,
                'display_order' => 2,
                'badge' => 'Popular',
                'highlight_plan' => true,
                'most_popular' => true,
                'plan_card_color' => '#2563EB',
                'icon_key' => 'bi-lightning-charge',
                'features' => [
                    'products_count' => ['enabled' => true, 'limit_value' => 500],
                    'vendor_panel' => ['enabled' => true],
                    'loyalty_points' => ['enabled' => true],
                    'gift_cards' => ['enabled' => true],
                    'promotions' => ['enabled' => true],
                    'reports' => ['enabled' => true],
                    'activity_log' => ['enabled' => true],
                    'seating_plan' => ['enabled' => true],
                    'kitchen' => ['enabled' => true],
                ],
            ],
            [
                'plan_name' => 'Enterprise POS',
                'plan_code' => 'VSP-ENTERPRISE',
                'short_description' => 'For multi-branch restaurant groups that need unlimited catalog capacity and all POS growth modules.',
                'status' => 'active',
                'is_default' => false,
                'monthly_price' => 12500,
                'yearly_price' => 127500,
                'yearly_discount_type' => 'percentage',
                'yearly_discount_value' => 15,
                'currency_code' => 'LKR',
                'trial_days' => 30,
                'auto_renew' => true,
                'display_order' => 3,
                'badge' => 'Enterprise',
                'highlight_plan' => true,
                'most_popular' => false,
                'plan_card_color' => '#7C3AED',
                'icon_key' => 'bi-rocket-takeoff',
                'features' => [
                    'products_count' => ['enabled' => true, 'is_unlimited' => true],
                    'vendor_panel' => ['enabled' => true],
                    'loyalty_points' => ['enabled' => true],
                    'gift_cards' => ['enabled' => true],
                    'promotions' => ['enabled' => true],
                    'reports' => ['enabled' => true],
                    'activity_log' => ['enabled' => true],
                    'seating_plan' => ['enabled' => true],
                    'kitchen' => ['enabled' => true],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = VendorSubscriptionPlan::updateOrCreate(
                ['plan_code' => $planData['plan_code']],
                $planData
            );

            if ($plan->is_default) {
                VendorSubscriptionPlan::query()
                    ->where('id', '!=', $plan->id)
                    ->update(['is_default' => false]);
            }

            $this->syncFeatures($plan, $features);
        }
    }

    private function syncFeatures(VendorSubscriptionPlan $plan, array $enabledFeatures): void
    {
        $catalog = [
            ['feature_key' => 'products_count', 'feature_name' => 'Products Count', 'feature_group' => 'Catalog', 'value_type' => 'limit', 'unit' => 'products', 'sort_order' => 10],
            ['feature_key' => 'vendor_panel', 'feature_name' => 'Vendor Panel Access', 'feature_group' => 'Access', 'value_type' => 'boolean', 'unit' => null, 'sort_order' => 20],
            ['feature_key' => 'loyalty_points', 'feature_name' => 'Loyalty Points', 'feature_group' => 'Customer Growth', 'value_type' => 'boolean', 'unit' => null, 'sort_order' => 30],
            ['feature_key' => 'gift_cards', 'feature_name' => 'Gift Cards', 'feature_group' => 'Customer Growth', 'value_type' => 'boolean', 'unit' => null, 'sort_order' => 40],
            ['feature_key' => 'promotions', 'feature_name' => 'Promotions', 'feature_group' => 'Customer Growth', 'value_type' => 'boolean', 'unit' => null, 'sort_order' => 50],
            ['feature_key' => 'reports', 'feature_name' => 'Reports', 'feature_group' => 'Insights', 'value_type' => 'boolean', 'unit' => null, 'sort_order' => 60],
            ['feature_key' => 'activity_log', 'feature_name' => 'Activity Log', 'feature_group' => 'Governance', 'value_type' => 'boolean', 'unit' => null, 'sort_order' => 70],
            ['feature_key' => 'seating_plan', 'feature_name' => 'Seating Plan', 'feature_group' => 'Restaurant Operations', 'value_type' => 'boolean', 'unit' => null, 'sort_order' => 80],
            ['feature_key' => 'kitchen', 'feature_name' => 'Kitchen', 'feature_group' => 'Restaurant Operations', 'value_type' => 'boolean', 'unit' => null, 'sort_order' => 90],
        ];

        foreach ($catalog as $feature) {
            $values = $enabledFeatures[$feature['feature_key']] ?? [];

            $plan->features()->updateOrCreate(
                ['feature_key' => $feature['feature_key']],
                [
                    'feature_name' => $feature['feature_name'],
                    'feature_group' => $feature['feature_group'],
                    'value_type' => $feature['value_type'],
                    'enabled' => (bool) ($values['enabled'] ?? false),
                    'is_unlimited' => (bool) ($values['is_unlimited'] ?? false),
                    'limit_value' => $values['limit_value'] ?? null,
                    'unit' => $feature['unit'],
                    'sort_order' => $feature['sort_order'],
                ]
            );
        }
    }
}
