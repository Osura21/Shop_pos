<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DiningTable;
use App\Models\Floor;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class SeatingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::query()->select('id', 'tenant_id', 'name')->get();

        foreach ($branches as $branch) {
            $floor1 = Floor::firstOrCreate(
                ['tenant_id' => $branch->tenant_id, 'branch_id' => $branch->id, 'name' => 'Floor 1'],
                ['is_active' => true]
            );

            $floor2 = Floor::firstOrCreate(
                ['tenant_id' => $branch->tenant_id, 'branch_id' => $branch->id, 'name' => 'Floor 2'],
                ['is_active' => true]
            );

            $zones = [
                ['floor_id' => $floor1->id, 'name' => 'Indoor'],
                ['floor_id' => $floor1->id, 'name' => 'Terrace'],
                ['floor_id' => $floor2->id, 'name' => 'Smoking Area'],
                ['floor_id' => $floor2->id, 'name' => 'Quiet Zone'],
            ];

            foreach ($zones as $zoneRow) {
                $zone = Zone::firstOrCreate(
                    [
                        'tenant_id' => $branch->tenant_id,
                        'branch_id' => $branch->id,
                        'floor_id' => $zoneRow['floor_id'],
                        'name' => $zoneRow['name'],
                    ],
                    ['is_active' => true]
                );

                for ($i = 1; $i <= 3; $i++) {
                    DiningTable::firstOrCreate(
                        [
                            'tenant_id' => $branch->tenant_id,
                            'branch_id' => $branch->id,
                            'name' => 'T0' . $i . '-' . $zone->id,
                        ],
                        [
                            'floor_id' => $zone->floor_id,
                            'zone_id' => $zone->id,
                            'shape' => 'square',
                            'capacity' => rand(2, 8),
                            'status' => 'available',
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}