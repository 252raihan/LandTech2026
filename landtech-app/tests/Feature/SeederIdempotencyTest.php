<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Mouza;
use App\Models\Party;
use App\Models\PartyIdentity;
use App\Models\Plot;
use App\Models\PlotOwnership;
use App\Models\Role;
use App\Models\Upazila;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3 — seeders must be safely re-runnable.
 *
 * These tests run the full DatabaseSeeder twice and assert that:
 *   - the second run succeeds (no unique-constraint collision),
 *   - record counts are unchanged (no duplicates),
 *   - existing primary keys are preserved (so FK references stay valid).
 */
class SeederIdempotencyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_seeder_runs_twice_without_error(): void
    {
        $this->seed();
        $this->seed();

        $this->assertTrue(true, 'second seed run completed without throwing');
    }

    public function test_geo_seeding_does_not_duplicate_or_reassign_ids(): void
    {
        $this->seed();

        $before = [
            'districts' => District::orderBy('id')->pluck('id')->all(),
            'upazilas' => Upazila::orderBy('id')->pluck('id')->all(),
            'mouzas' => Mouza::orderBy('id')->pluck('id')->all(),
        ];

        $this->seed();

        $this->assertSame(3, District::count());
        $this->assertSame(4, Upazila::count());
        $this->assertSame(5, Mouza::count());

        $this->assertSame($before['districts'], District::orderBy('id')->pluck('id')->all());
        $this->assertSame($before['upazilas'], Upazila::orderBy('id')->pluck('id')->all());
        $this->assertSame($before['mouzas'], Mouza::orderBy('id')->pluck('id')->all());
    }

    public function test_party_seeding_does_not_duplicate_records(): void
    {
        $this->seed();

        $counts = [
            'users' => User::count(),
            'roles' => Role::count(),
            'parties' => Party::count(),
            'identities' => PartyIdentity::count(),
            'plots' => Plot::count(),
            'ownerships' => PlotOwnership::count(),
        ];

        $this->seed();

        $this->assertSame($counts['users'], User::count(), 'users duplicated');
        $this->assertSame($counts['roles'], Role::count(), 'roles duplicated');
        $this->assertSame($counts['parties'], Party::count(), 'parties duplicated');
        $this->assertSame($counts['identities'], PartyIdentity::count(), 'identities duplicated');
        $this->assertSame($counts['plots'], Plot::count(), 'plots duplicated');
        $this->assertSame($counts['ownerships'], PlotOwnership::count(), 'ownerships duplicated');
    }

    public function test_ownership_shares_remain_exact_fractions_after_reseeding(): void
    {
        $this->seed();
        $this->seed();

        $thirds = PlotOwnership::where('share_denominator', 3)->get();

        $this->assertCount(3, $thirds);
        $this->assertSame(['1/3', '1/3', '1/3'], $thirds->map->shareLabel()->all());

        $sole = PlotOwnership::where('ownership_role', PlotOwnership::ROLE_SOLE)->get();
        $this->assertCount(1, $sole);
        $this->assertSame('1/1', $sole->first()->shareLabel());
    }

    public function test_plot_mouza_references_survive_a_reseed(): void
    {
        $this->seed();

        $refs = Plot::orderBy('id')->pluck('mouza_id')->all();

        $this->seed();

        $this->assertSame($refs, Plot::orderBy('id')->pluck('mouza_id')->all());

        // Every plot must still resolve to a real mouza.
        foreach ($refs as $mouzaId) {
            $this->assertTrue(Mouza::whereKey($mouzaId)->exists());
        }
    }

    public function test_bengali_seeded_identity_is_still_findable_after_reseed(): void
    {
        $this->seed();
        $this->seed();

        $hashes = array_values(PartyIdentity::lookupHashesFor('SIM-1002'));
        $found = PartyIdentity::whereIn('id_number_lookup_hmac', $hashes)->first();

        $this->assertNotNull($found, 'Bengali-digit identity lost after reseed');
        $this->assertSame(1, PartyIdentity::whereIn('id_number_lookup_hmac', $hashes)->count());
    }

    public function test_reseeding_preserves_the_registry_officer_role_assignment(): void
    {
        $this->seed();

        $roleId = Role::where('name', Role::REGISTRY_OFFICER)->value('id');
        $before = DB::table('role_user')->where('role_id', $roleId)->count();

        $this->seed();

        $after = DB::table('role_user')->where('role_id', $roleId)->count();

        $this->assertSame($before, $after, 'role assignment duplicated on reseed');
        $this->assertGreaterThan(0, $after);
    }
}
