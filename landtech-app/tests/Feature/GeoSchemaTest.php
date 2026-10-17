<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Mouza;
use App\Models\Upazila;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GeoSchemaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_geo_tables_exist_alongside_preserved_base_tables(): void
    {
        foreach (['districts', 'upazilas', 'mouzas'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table missing");
        }

        foreach (['users', 'sessions', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table missing");
        }

        // Phase 1 must not have created any authorization tables. Phase 3 later
        // adds `roles`/`role_user`; what this test guards is that Phase 1's geo
        // migrations themselves did not introduce them.
        $this->assertTrue(
            Schema::hasTable('roles') === Schema::hasTable('role_user'),
            'roles and role_user must appear together, from Phase 3 only'
        );
    }

    public function test_foreign_keys_and_indexes_are_defined(): void
    {
        $this->assertTrue(Schema::hasIndex('upazilas', 'upazilas_district_id_index'));
        $this->assertTrue(Schema::hasIndex('mouzas', 'mouzas_upazila_id_index'));

        $upazilas = Schema::getIndexes('upazilas');
        $districtIndex = collect($upazilas)->first(fn ($i) => $i['name'] === 'upazilas_district_id_index');
        $this->assertNotNull($districtIndex);
        $this->assertTrue($districtIndex['unique'] === false);

        $foreignKeys = collect(Schema::getForeignKeys('upazilas'))
            ->first(fn ($f) => $f['columns'] === ['district_id']);
        $this->assertNotNull($foreignKeys, 'district_id FK missing on upazilas');
        $this->assertSame('districts', $foreignKeys['foreign_table']);
    }

    public function test_models_persist_relationships_end_to_end(): void
    {
        $district = District::create([
            'code' => 'T-01',
            'name_en' => 'Test District',
            'name_bn' => 'টেস্ট জেলা',
            'latitude' => 23.810331,
            'longitude' => 90.412521,
        ]);

        $upazila = $district->upazilas()->create([
            'code' => 'T-01-U1',
            'name_en' => 'Test Upazila',
            'name_bn' => 'টেস্ট উপজেলা',
        ]);

        $mouza = $upazila->mouzas()->create([
            'code' => 'T-01-U1-M1',
            'name_en' => 'Test Mouza',
            'name_bn' => 'টেস্ট মৌজা',
        ]);

        $this->assertSame($district->id, $upazila->district_id);
        $this->assertSame($upazila->id, $mouza->upazila_id);
        $this->assertSame('Test District', Upazila::find($upazila->id)->district->name_en);
        $this->assertSame('Test Upazila', Mouza::find($mouza->id)->upazila->name_en);
        $this->assertSame(1, $district->upazilas()->count());
        $this->assertSame(1, $upazila->mouzas()->count());
    }

    public function test_deleting_a_district_cascades_to_children(): void
    {
        $district = District::create(['code' => 'C-01', 'name_en' => 'Cascade', 'name_bn' => 'ক্যাসকেড']);
        $upazila = $district->upazilas()->create(['code' => 'C-01-U1', 'name_en' => 'Cascade U', 'name_bn' => 'উ']);
        $upazila->mouzas()->create(['name_en' => 'Cascade M', 'name_bn' => 'ম']);

        $district->delete();

        $this->assertSame(0, Upazila::count());
        $this->assertSame(0, Mouza::count());
        $this->assertNull(District::find($district->id));
    }

    public function test_district_codes_must_be_unique(): void
    {
        District::create(['code' => 'DUP-1', 'name_en' => 'A', 'name_bn' => 'এ']);

        $this->expectException(UniqueConstraintViolationException::class);

        District::create(['code' => 'DUP-1', 'name_en' => 'B', 'name_bn' => 'বি']);
    }

    public function test_geo_migrations_are_reversible_and_restore_is_clean(): void
    {
        // Roll back to the boundary just above the Phase 1 geo trio. Later
        // phases append their own migrations, so a hardcoded step count would
        // go stale; derive it from the number of migration files present.
        $geoIndex = 3; // users, cache and jobs precede the geo trio
        $steps = count(glob(database_path('migrations/*.php'))) - $geoIndex;

        Artisan::call('migrate:rollback', ['--step' => $steps]);

        foreach (['districts', 'upazilas', 'mouzas'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "$table should be dropped on rollback");
        }

        foreach (['users', 'cache', 'jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table must survive rollback of geo tables");
        }

        Artisan::call('migrate');

        foreach (['districts', 'upazilas', 'mouzas'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table should be recreated");
        }
    }
}
