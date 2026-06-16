<?php

namespace Database\Seeders;

use App\Models\LoyaltyProgram;
use App\Models\LoyaltyPromotion;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyTier;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class LoyaltySeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->select('id')->chunkById(50, function ($tenants) {
            foreach ($tenants as $tenant) {
                $program = LoyaltyProgram::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => 'Restaurant Rewards'],
                    [
                        'earning_rate' => 0.100000,
                        'points_expire_after_days' => 365,
                        'is_active' => true,
                    ],
                );

                $bronze = LoyaltyTier::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Bronze'],
                    ['minimum_spend' => 0, 'multiplier' => 1, 'sort_order' => 1, 'is_active' => true],
                );

                LoyaltyTier::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Silver'],
                    ['minimum_spend' => 500, 'multiplier' => 1.2, 'sort_order' => 2, 'is_active' => true],
                );

                $gold = LoyaltyTier::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Gold'],
                    ['minimum_spend' => 1500, 'multiplier' => 1.5, 'sort_order' => 3, 'is_active' => true],
                );

                LoyaltyReward::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Discount Reward'],
                    [
                        'loyalty_tier_id' => $bronze->id,
                        'type' => 'discount',
                        'points_cost' => 250,
                        'value_type' => 'fixed',
                        'value' => 10,
                        'minimum_order_total' => 0,
                        'is_active' => true,
                    ],
                );

                LoyaltyReward::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Voucher Reward'],
                    [
                        'loyalty_tier_id' => $bronze->id,
                        'type' => 'voucher_code',
                        'points_cost' => 400,
                        'value_type' => 'fixed',
                        'value' => 15,
                        'code_prefix' => 'LOY',
                        'expires_in_days' => 30,
                        'is_active' => true,
                    ],
                );

                LoyaltyReward::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Tier Upgrade Reward'],
                    [
                        'loyalty_tier_id' => $bronze->id,
                        'type' => 'tier_upgrade',
                        'points_cost' => 800,
                        'target_tier_id' => $gold->id,
                        'is_active' => true,
                    ],
                );

                LoyaltyPromotion::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'loyalty_program_id' => $program->id, 'name' => 'Weekend Double Points'],
                    [
                        'type' => 'multiplier',
                        'multiplier' => 2,
                        'available_days' => ['saturday', 'sunday'],
                        'is_active' => true,
                    ],
                );
            }
        });
    }
}
