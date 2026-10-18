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
use Database\Seeders\DatabaseSeeder;
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

    /**
     * Phase 4 / H1 — the officer role must land on the DESIGNATED demo account
     * only, and must never be attached to an unrelated pre-existing user.
     */
    public function test_only_the_designated_demo_officer_receives_the_registry_officer_role(): void
    {
        $this->seed();

        $roleId = Role::where('name', Role::REGISTRY_OFFICER)->value('id');

        $holderEmails = DB::table('role_user')
            ->join('users', 'users.id', '=', 'role_user.user_id')
            ->where('role_user.role_id', $roleId)
            ->pluck('users.email')
            ->all();

        $this->assertSame(
            [DatabaseSeeder::DEMO_ACCOUNTS[Role::REGISTRY_OFFICER]],
            $holderEmails,
            'registry_officer must be held by exactly the designated demo officer'
        );
    }

    /**
     * A pre-existing, unrelated user must NOT be promoted merely because it
     * happens to be the first row in the users table.
     */
    public function test_a_preexisting_unrelated_user_is_never_promoted(): void
    {
        // Create the decoy BEFORE seeding so it is genuinely user #1 — the exact
        // condition that previously triggered the unsafe promotion.
        $decoy = User::factory()->create(['email' => 'unrelated.first@example.test']);

        $this->assertSame($decoy->id, User::query()->min('id'), 'decoy must be the lowest-id user');

        $this->seed();

        $this->assertFalse(
            $decoy->fresh()->hasRole(Role::REGISTRY_OFFICER),
            'an unrelated pre-existing user must never be granted registry_officer'
        );
        $this->assertSame(0, $decoy->fresh()->roles()->count(), 'decoy must hold no roles at all');
    }

    /**
     * Outside local/testing, seeding must create no privileged role holder at
     * all — production seeding can never escalate an existing account.
     */
    public function test_privileged_roles_are_not_granted_outside_local_and_testing(): void
    {
        app()->detectEnvironment(fn () => 'production');

        try {
            // Invoke the seeder class directly: `$this->seed()` would trigger
            // Laravel's interactive production confirmation prompt.
            (new DatabaseSeeder())->run();

            $this->assertSame(
                0,
                DB::table('role_user')->count(),
                'no role assignment may be created when seeding outside local/testing'
            );

            foreach (DatabaseSeeder::DEMO_ACCOUNTS as $email) {
                $this->assertFalse(
                    User::query()->where('email', $email)->exists(),
                    'demo accounts must not be created in production'
                );
            }
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    /**
     * Phase 4 — every implemented role must have its own seeded demo account,
     * each holding exactly its own role (no cross-granting).
     */
    public function test_each_implemented_role_has_its_own_demo_account(): void
    {
        $this->seed();

        foreach (DatabaseSeeder::DEMO_ACCOUNTS as $roleName => $email) {
            $user = User::query()->where('email', $email)->first();

            $this->assertNotNull($user, "missing demo account for {$roleName}");
            $this->assertTrue($user->hasRole($roleName), "{$email} must hold {$roleName}");

            // Exactly one role: no demo account is over-privileged.
            $this->assertSame(
                1,
                $user->roles()->count(),
                "{$email} must hold exactly one role"
            );
        }
    }
}
