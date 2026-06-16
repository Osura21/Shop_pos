<?php

namespace Database\Seeders;

use App\Models\Tax;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($tenantIds as $tenantId) {
            Tax::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code' => 'SERVICE_10_GLOBAL',
                ],
                [
                    'branch_id' => null,
                    'name' => 'Service Charge 10%',
                    'rate' => 10,
                    'type' => 'exclusive',
                    'is_compound' => true,
                    'is_global' => true,
                    'is_active' => true,
                    'order_types' => ['dine_in'],
                ]
            );

            Tax::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code' => 'VAT_15',
                ],
                [
                    'branch_id' => null,
                    'name' => 'VAT 15%',
                    'rate' => 15,
                    'type' => 'exclusive',
                    'is_compound' => false,
                    'is_global' => true,
                    'is_active' => true,
                    'order_types' => ['takeaway', 'dine_in', 'pick_up', 'drive_thru', 'pre_order', 'catering'],
                ]
            );
        }
    }
}