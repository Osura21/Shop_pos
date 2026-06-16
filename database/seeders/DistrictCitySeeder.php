<?php
namespace Database\Seeders;

use App\Models\City;
use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictCitySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/cities-by-district.json');
        $data = json_decode(file_get_contents($path), true);

        foreach ($data as $districtName => $payload) {
            $district = District::firstOrCreate(['name' => $districtName]);

            $cities = $payload['cities'] ?? [];
            foreach ($cities as $cityName) {
                City::firstOrCreate([
                    'district_id' => $district->id,
                    'name' => $cityName,
                ]);
            }
        }
    }
}
