<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        Theme::updateOrCreate(
            ['slug' => 'classic'],
            ['name' => 'Classic', 'path' => 'Themes/classic', 'is_active' => true]
        );

        Theme::updateOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'path' => 'Themes/modern', 'is_active' => true]
        );
    }
}

