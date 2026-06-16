<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FoodCitySampleSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()
            ->select('id')
            ->orderBy('id')
            ->chunk(50, function ($tenants) {
                foreach ($tenants as $tenant) {
                    $this->seedTenant((int) $tenant->id);
                }
            });
    }

    private function seedTenant(int $tenantId): void
    {
        $menu = Menu::withTrashed()->firstOrNew([
            'tenant_id' => $tenantId,
            'name' => 'Food City Menu',
        ]);

        $menu->fill([
            'branch_ids' => null,
            'description' => 'Sample shop POS menu for barcode sales.',
            'is_active' => true,
        ]);
        $menu->save();
        if ($menu->trashed()) {
            $menu->restore();
        }

        $categories = [
            'Rice' => [
                'Nadu Rice 5kg',
                'Samba Rice 5kg',
            ],
            'Milk Powder' => [
                'Anchor Milk Powder 400g',
                'Raththi Milk Powder 400g',
            ],
            'Biscuits' => [
                'Munchee Chocolate Puff 200g',
                'Maliban Cream Cracker 190g',
            ],
            'Soft Drinks' => [
                'Coca Cola 1.5L',
                'Elephant House Cream Soda 1L',
            ],
            'Soap' => [
                'Lifebuoy Soap 100g',
                'Sunlight Soap 115g',
            ],
        ];

        $productRows = [
            'Nadu Rice 5kg' => ['sku' => '4790001000011', 'brand' => 'Araliya', 'unit_type' => 'pack', 'cost_price' => 1100, 'base_price' => 1250, 'current_stock' => 30, 'reorder_level' => 5],
            'Samba Rice 5kg' => ['sku' => '4790001000028', 'brand' => 'Araliya', 'unit_type' => 'pack', 'cost_price' => 1250, 'base_price' => 1420, 'current_stock' => 24, 'reorder_level' => 5],
            'Anchor Milk Powder 400g' => ['sku' => '4790002000010', 'brand' => 'Anchor', 'unit_type' => 'pcs', 'cost_price' => 1060, 'base_price' => 1180, 'current_stock' => 20, 'reorder_level' => 6],
            'Raththi Milk Powder 400g' => ['sku' => '4790002000027', 'brand' => 'Raththi', 'unit_type' => 'pcs', 'cost_price' => 1030, 'base_price' => 1160, 'current_stock' => 18, 'reorder_level' => 6],
            'Munchee Chocolate Puff 200g' => ['sku' => '4790003000019', 'brand' => 'Munchee', 'unit_type' => 'pcs', 'cost_price' => 310, 'base_price' => 380, 'current_stock' => 50, 'reorder_level' => 10],
            'Maliban Cream Cracker 190g' => ['sku' => '4790003000026', 'brand' => 'Maliban', 'unit_type' => 'pcs', 'cost_price' => 280, 'base_price' => 340, 'current_stock' => 45, 'reorder_level' => 10],
            'Coca Cola 1.5L' => ['sku' => '4790004000018', 'brand' => 'Coca Cola', 'unit_type' => 'pcs', 'cost_price' => 390, 'base_price' => 480, 'current_stock' => 36, 'reorder_level' => 8],
            'Elephant House Cream Soda 1L' => ['sku' => '4790004000025', 'brand' => 'Elephant House', 'unit_type' => 'pcs', 'cost_price' => 270, 'base_price' => 340, 'current_stock' => 32, 'reorder_level' => 8],
            'Lifebuoy Soap 100g' => ['sku' => '4790005000017', 'brand' => 'Lifebuoy', 'unit_type' => 'pcs', 'cost_price' => 135, 'base_price' => 180, 'current_stock' => 60, 'reorder_level' => 12],
            'Sunlight Soap 115g' => ['sku' => '4790005000024', 'brand' => 'Sunlight', 'unit_type' => 'pcs', 'cost_price' => 145, 'base_price' => 190, 'current_stock' => 55, 'reorder_level' => 12],
        ];

        foreach (array_keys($categories) as $index => $name) {
            $category = Category::withTrashed()->firstOrNew([
                'tenant_id' => $tenantId,
                'slug' => Str::slug($name),
            ]);

            $category->fill([
                'menu_id' => $menu->id,
                'parent_id' => null,
                'food_category_id' => null,
                'name' => $name,
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
            $category->save();
            if ($category->trashed()) {
                $category->restore();
            }

            foreach ($categories[$name] as $productName) {
                $row = $productRows[$productName];

                $product = Product::withTrashed()->firstOrNew([
                    'tenant_id' => $tenantId,
                    'sku' => $row['sku'],
                ]);

                $product->fill([
                    'menu_id' => $menu->id,
                    'name' => $productName,
                    'brand' => $row['brand'],
                    'unit_type' => $row['unit_type'],
                    'description' => "Sample {$name} product for shop POS barcode testing.",
                    'base_price' => $row['base_price'],
                    'secondary_price' => null,
                    'cost_price' => $row['cost_price'],
                    'current_stock' => $row['current_stock'],
                    'reorder_level' => $row['reorder_level'],
                    'special_price_type' => 'fixed',
                    'base_special_price' => null,
                    'secondary_special_price' => null,
                    'special_price_start' => null,
                    'special_price_end' => null,
                    'new_from' => null,
                    'new_to' => null,
                    'is_active' => true,
                ]);
                $product->save();
                if ($product->trashed()) {
                    $product->restore();
                }

                $product->categories()->syncWithoutDetaching([$category->id]);
            }
        }
    }
}
