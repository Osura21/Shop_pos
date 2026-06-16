<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\OptionTemplate;
use App\Models\OptionTemplateValue;
use Illuminate\Database\Seeder;

class OptionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::query()->select('id', 'tenant_id')->get();

        foreach ($branches as $branch) {
            $items = [
                [
                    'name' => 'Pickup Time',
                    'type' => 'time',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [],
                ],
                [
                    'name' => 'Pickup Date',
                    'type' => 'date',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [],
                ],
                [
                    'name' => 'Name on Cup',
                    'type' => 'text',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [],
                ],
                [
                    'name' => 'Special Instructions',
                    'type' => 'textarea',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [],
                ],
                [
                    'name' => 'Extra Shot',
                    'type' => 'checkbox',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [
                        ['label' => 'Add Extra Shot', 'base_price' => 0.500, 'secondary_price' => null, 'price_type' => 'fixed'],
                    ],
                ],
                [
                    'name' => 'Ice Level',
                    'type' => 'radio_button',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [
                        ['label' => 'No Ice', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => 'Less Ice', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => 'Regular Ice', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                    ],
                ],
                [
                    'name' => 'Sugar Level',
                    'type' => 'radio_button',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [
                        ['label' => 'No Sugar', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => 'Low Sugar', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => 'Regular Sugar', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                    ],
                ],
                [
                    'name' => 'Milk Choice',
                    'type' => 'select',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [
                        ['label' => 'Full Cream', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => 'Skim Milk', 'base_price' => 0.250, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => 'Soy Milk', 'base_price' => 0.500, 'secondary_price' => null, 'price_type' => 'fixed'],
                    ],
                ],
                [
                    'name' => 'Toppings',
                    'type' => 'multiple_select',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [
                        ['label' => 'Olives', 'base_price' => 0.800, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => 'Peppers', 'base_price' => 0.600, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => 'Onions', 'base_price' => 0.500, 'secondary_price' => null, 'price_type' => 'fixed'],
                    ],
                ],
                [
                    'name' => 'Pizza Cut',
                    'type' => 'select',
                    'is_required' => false,
                    'base_price' => 0,
                    'secondary_price' => null,
                    'price_type' => 'fixed',
                    'values' => [
                        ['label' => '4 Pieces', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => '6 Pieces', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                        ['label' => '8 Pieces', 'base_price' => 0, 'secondary_price' => null, 'price_type' => 'fixed'],
                    ],
                ],
            ];

            foreach ($items as $item) {
                $template = OptionTemplate::firstOrCreate(
                    [
                        'tenant_id' => $branch->tenant_id,
                        'branch_id' => $branch->id,
                        'name' => $item['name'],
                    ],
                    [
                        'type' => $item['type'],
                        'is_required' => $item['is_required'],
                        'base_price' => $item['base_price'],
                        'secondary_price' => $item['secondary_price'],
                        'price_type' => $item['price_type'],
                    ]
                );

                if ($template->values()->count() === 0) {
                    foreach ($item['values'] as $index => $row) {
                        OptionTemplateValue::create([
                            'option_template_id' => $template->id,
                            'label' => $row['label'],
                            'base_price' => $row['base_price'],
                            'secondary_price' => $row['secondary_price'],
                            'price_type' => $row['price_type'],
                            'sort_order' => $index,
                        ]);
                    }
                }
            }
        }
    }
}