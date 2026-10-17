<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1 — geographic reference data (district > upazila > mouza).
 *
 * IDEMPOTENT: uses upsert keyed on the natural unique key (`code` for
 * districts, upazilas and mouzas — each of those columns carries a real unique
 * index), so running the seeder twice updates existing rows in place instead of
 * colliding on the unique index or creating duplicates.
 *
 * IDs ARE PRESERVED. `upsert` updates matching rows rather than deleting and
 * re-inserting, so existing district/upazila/mouza primary keys — and therefore
 * any `plots.mouza_id` references — survive a re-seed unchanged.
 *
 * No destructive operation (migrate:fresh / db:wipe / truncate) is used.
 */
class GeoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $districts = [
            ['code' => 'DHA', 'name_en' => 'Dhaka', 'name_bn' => 'ঢাকা', 'division_en' => 'Dhaka', 'division_bn' => 'ঢাকা', 'latitude' => 23.810300, 'longitude' => 90.412500],
            ['code' => 'CTG', 'name_en' => 'Chattogram', 'name_bn' => 'চট্টগ্রাম', 'division_en' => 'Chattogram', 'division_bn' => 'চট্টগ্রাম', 'latitude' => 22.356900, 'longitude' => 91.783200],
            ['code' => 'SYL', 'name_en' => 'Sylhet', 'name_bn' => 'সিলেট', 'division_en' => 'Sylhet', 'division_bn' => 'সিলেট', 'latitude' => 24.894900, 'longitude' => 91.868700],
        ];

        // Keyed on the unique `code` column: matched rows are updated in place,
        // so their `id` values are never reassigned.
        DB::table('districts')->upsert(
            array_map(fn ($d) => $d + ['created_at' => $now, 'updated_at' => $now], $districts),
            ['code'],
            ['name_en', 'name_bn', 'division_en', 'division_bn', 'latitude', 'longitude', 'updated_at']
        );

        $districtIds = DB::table('districts')->pluck('id', 'code');

        $upazilas = [
            ['district_code' => 'DHA', 'code' => 'DHA-01', 'name_en' => 'Dhamrai', 'name_bn' => 'ধামরাই', 'latitude' => 23.862200, 'longitude' => 90.166800],
            ['district_code' => 'DHA', 'code' => 'DHA-02', 'name_en' => 'Savar', 'name_bn' => 'সাভার', 'latitude' => 23.858300, 'longitude' => 90.266700],
            ['district_code' => 'CTG', 'code' => 'CTG-01', 'name_en' => 'Raozan', 'name_bn' => 'রাউজান', 'latitude' => 22.495600, 'longitude' => 91.910000],
            ['district_code' => 'SYL', 'code' => 'SYL-01', 'name_en' => 'Beanibazar', 'name_bn' => 'বেনীবাজার', 'latitude' => 24.775300, 'longitude' => 92.041400],
        ];

        DB::table('upazilas')->upsert(
            array_map(fn ($u) => [
                'district_id' => $districtIds[$u['district_code']],
                'code' => $u['code'],
                'name_en' => $u['name_en'],
                'name_bn' => $u['name_bn'],
                'latitude' => $u['latitude'],
                'longitude' => $u['longitude'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $upazilas),
            ['code'],
            ['district_id', 'name_en', 'name_bn', 'latitude', 'longitude', 'updated_at']
        );

        $upazilaIds = DB::table('upazilas')->pluck('id', 'code');

        $mouzas = [
            ['upazila_code' => 'DHA-01', 'code' => 'DHA-01-M01', 'name_en' => 'Jalaldi', 'name_bn' => 'জালালদী', 'latitude' => 23.855100, 'longitude' => 90.188300],
            ['upazila_code' => 'DHA-02', 'code' => 'DHA-02-M01', 'name_en' => 'Amtala', 'name_bn' => 'আমতলা', 'latitude' => 23.861200, 'longitude' => 90.274500],
            ['upazila_code' => 'CTG-01', 'code' => 'CTG-01-M01', 'name_en' => 'Noapara', 'name_bn' => 'নোয়াপাড়া', 'latitude' => 22.503100, 'longitude' => 91.925800],
            ['upazila_code' => 'SYL-01', 'code' => 'SYL-01-M01', 'name_en' => 'Dewrali', 'name_bn' => 'দেওড়ালী', 'latitude' => 24.780900, 'longitude' => 92.057100],
            ['upazila_code' => 'SYL-01', 'code' => 'SYL-01-M02', 'name_en' => 'Patli', 'name_bn' => 'পাটলী', 'latitude' => 24.768800, 'longitude' => 92.030200],
        ];

        // Conflict target must match a REAL unique index. `mouzas` has
        // `mouzas_code_unique` on `code` alone (there is no composite
        // (upazila_id, code) unique index), and SQLite rejects an upsert whose
        // conflict target does not correspond to an actual unique constraint.
        // Since `code` is globally unique here, keying on it is both valid and
        // sufficient.
        DB::table('mouzas')->upsert(
            array_map(fn ($m) => [
                'upazila_id' => $upazilaIds[$m['upazila_code']],
                'code' => $m['code'],
                'name_en' => $m['name_en'],
                'name_bn' => $m['name_bn'],
                'latitude' => $m['latitude'],
                'longitude' => $m['longitude'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $mouzas),
            ['code'],
            ['upazila_id', 'name_en', 'name_bn', 'latitude', 'longitude', 'updated_at']
        );
    }
}
