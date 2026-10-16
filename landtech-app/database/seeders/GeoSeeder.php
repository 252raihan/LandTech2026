<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            ['code' => 'DHA', 'name_en' => 'Dhaka', 'name_bn' => 'ঢাকা', 'division_en' => 'Dhaka', 'division_bn' => 'ঢাকা', 'latitude' => 23.810300, 'longitude' => 90.412500],
            ['code' => 'CTG', 'name_en' => 'Chattogram', 'name_bn' => 'চট্টগ্রাম', 'division_en' => 'Chattogram', 'division_bn' => 'চট্টগ্রাম', 'latitude' => 22.356900, 'longitude' => 91.783200],
            ['code' => 'SYL', 'name_en' => 'Sylhet', 'name_bn' => 'সিলেট', 'division_en' => 'Sylhet', 'division_bn' => 'সিলেট', 'latitude' => 24.894900, 'longitude' => 91.868700],
        ];

        foreach ($districts as $d) {
            DB::table('districts')->insert($d + ['created_at' => now(), 'updated_at' => now()]);
        }

        $districtIds = DB::table('districts')->pluck('id', 'code');

        $upazilas = [
            ['district_code' => 'DHA', 'code' => 'DHA-01', 'name_en' => 'Dhamrai', 'name_bn' => 'ধামরাই', 'latitude' => 23.862200, 'longitude' => 90.166800],
            ['district_code' => 'DHA', 'code' => 'DHA-02', 'name_en' => 'Savar', 'name_bn' => 'সাভার', 'latitude' => 23.858300, 'longitude' => 90.266700],
            ['district_code' => 'CTG', 'code' => 'CTG-01', 'name_en' => 'Raozan', 'name_bn' => 'রাউজান', 'latitude' => 22.495600, 'longitude' => 91.910000],
            ['district_code' => 'SYL', 'code' => 'SYL-01', 'name_en' => 'Beanibazar', 'name_bn' => 'বেনীবাজার', 'latitude' => 24.775300, 'longitude' => 92.041400],
        ];

        foreach ($upazilas as $u) {
            DB::table('upazilas')->insert([
                'district_id' => $districtIds[$u['district_code']],
                'code' => $u['code'],
                'name_en' => $u['name_en'],
                'name_bn' => $u['name_bn'],
                'latitude' => $u['latitude'],
                'longitude' => $u['longitude'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $upazilaIds = DB::table('upazilas')->pluck('id', 'code');

        $mouzas = [
            ['upazila_code' => 'DHA-01', 'code' => 'DHA-01-M01', 'name_en' => 'Jalaldi', 'name_bn' => 'জালালদী', 'latitude' => 23.855100, 'longitude' => 90.188300],
            ['upazila_code' => 'DHA-02', 'code' => 'DHA-02-M01', 'name_en' => 'Amtala', 'name_bn' => 'আমতলা', 'latitude' => 23.861200, 'longitude' => 90.274500],
            ['upazila_code' => 'CTG-01', 'code' => 'CTG-01-M01', 'name_en' => 'Noapara', 'name_bn' => 'নোয়াপাড়া', 'latitude' => 22.503100, 'longitude' => 91.925800],
            ['upazila_code' => 'SYL-01', 'code' => 'SYL-01-M01', 'name_en' => 'Dewrali', 'name_bn' => 'দেওড়ালী', 'latitude' => 24.780900, 'longitude' => 92.057100],
            ['upazila_code' => 'SYL-01', 'code' => 'SYL-01-M02', 'name_en' => 'Patli', 'name_bn' => 'পাটলী', 'latitude' => 24.768800, 'longitude' => 92.030200],
        ];

        foreach ($mouzas as $m) {
            DB::table('mouzas')->insert([
                'upazila_id' => $upazilaIds[$m['upazila_code']],
                'code' => $m['code'],
                'name_en' => $m['name_en'],
                'name_bn' => $m['name_bn'],
                'latitude' => $m['latitude'],
                'longitude' => $m['longitude'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
