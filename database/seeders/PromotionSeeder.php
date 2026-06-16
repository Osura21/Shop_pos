<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\PromotionDiscount;
use App\Models\PromotionVoucher;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->select('id')->get()->each(function ($tenant) {
            $branchId = Branch::where('tenant_id', $tenant->id)->value('id');

            PromotionDiscount::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Lunch 10% Discount'],
                [
                    'branch_id' => $branchId,
                    'description' => 'Sample active percentage discount for POS.',
                    'type' => 'percentage',
                    'value' => 10,
                    'max_discount' => 500,
                    'min_spend' => 1000,
                    'order_types' => ['takeaway', 'dine_in', 'pick_up'],
                    'available_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                    'is_active' => true,
                    'starts_at' => now()->subDays(5)->toDateString(),
                    'ends_at' => now()->addMonths(2)->toDateString(),
                ]
            );

            PromotionDiscount::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Family Fixed Discount'],
                [
                    'branch_id' => $branchId,
                    'description' => 'Sample fixed discount for larger carts.',
                    'type' => 'fixed',
                    'value' => 250,
                    'secondary_value' => 1,
                    'min_spend' => 2500,
                    'is_active' => true,
                    'starts_at' => now()->subDays(2)->toDateString(),
                    'ends_at' => now()->addMonths(1)->toDateString(),
                ]
            );

            PromotionVoucher::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'WELCOME10T' . $tenant->id],
                [
                    'branch_id' => $branchId,
                    'name' => 'Welcome Voucher',
                    'description' => 'Sample voucher for new customers.',
                    'type' => 'percentage',
                    'value' => 10,
                    'max_discount' => 400,
                    'min_spend' => 1000,
                    'usage_limit' => 250,
                    'per_customer_limit' => 1,
                    'order_types' => ['takeaway', 'dine_in', 'pick_up'],
                    'is_active' => true,
                    'starts_at' => now()->subDays(5)->toDateString(),
                    'ends_at' => now()->addMonths(2)->toDateString(),
                ]
            );

            PromotionVoucher::firstOrCreate(
                ['tenant_id' => $tenant->id, 'code' => 'SAVE300T' . $tenant->id],
                [
                    'branch_id' => $branchId,
                    'name' => 'Fixed Save Voucher',
                    'description' => 'Sample fixed voucher.',
                    'type' => 'fixed',
                    'value' => 300,
                    'secondary_value' => 1,
                    'min_spend' => 1800,
                    'usage_limit' => 100,
                    'is_active' => true,
                    'starts_at' => now()->subDays(5)->toDateString(),
                    'ends_at' => now()->addMonths(1)->toDateString(),
                ]
            );
        });
    }
}
