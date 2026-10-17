<?php

namespace Tests\Feature;

use App\Models\Mouza;
use App\Models\Party;
use App\Models\PartyIdentity;
use App\Models\Plot;
use App\Models\PlotOwnership;
use App\Models\Role;
use App\Models\SensitiveReadAudit;
use App\Models\Upazila;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 3 schema contract: tables exist, FKs/indexes are correct, relationships
 * resolve end to end, and the migrations are cleanly reversible without
 * disturbing the Phase 1/2 tables.
 */
class PartySchemaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_phase_three_tables_exist_alongside_preserved_tables(): void
    {
        foreach (['roles', 'role_user', 'parties', 'party_identities', 'plots', 'plot_ownerships', 'sensitive_read_audits'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table missing");
        }

        // Phase 1 + 2 tables must survive untouched.
        foreach (['users', 'sessions', 'password_reset_tokens', 'cache', 'jobs', 'districts', 'upazilas', 'mouzas'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table must be preserved");
        }
    }

    public function test_users_table_schema_is_unchanged_by_phase_three(): void
    {
        $columns = array_map(
            fn($c) => $c['name'],
            Schema::getColumns('users')
        );

        // Exactly the Phase 2 columns — no Phase 3 additions allowed.
        $this->assertSame(
            ['id', 'name', 'email', 'email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'],
            $columns
        );
    }

    public function test_identity_lookup_index_is_over_the_hmac_not_the_ciphertext(): void
    {
        $indexes = collect(Schema::getIndexes('party_identities'));
        $unique = $indexes->first(fn($i) => $i['unique'] === true);

        $this->assertNotNull($unique, 'expected a unique index on party_identities');
        $this->assertSame(['id_type', 'id_number_lookup_hmac'], $unique['columns']);

        // The encrypted payload must never be indexed or constrained.
        foreach ($indexes as $index) {
            $this->assertNotContains(
                'id_number_encrypted',
                $index['columns'],
                'id_number_encrypted must never be indexed — it is randomized ciphertext'
            );
        }
    }

    public function test_audit_chain_position_is_unique(): void
    {
        $indexes = collect(Schema::getIndexes('sensitive_read_audits'));
        $unique = $indexes->first(fn($i) => $i['unique'] === true && $i['columns'] === ['chain_position']);

        $this->assertNotNull($unique, 'chain_position must be unique to prevent chain forks');
    }

    public function test_audit_table_has_no_updated_at_column(): void
    {
        $columns = array_map(fn($c) => $c['name'], Schema::getColumns('sensitive_read_audits'));

        $this->assertContains('created_at', $columns);
        $this->assertNotContains('updated_at', $columns, 'audit rows must be immutable');
    }

    public function test_party_identity_and_ownership_relationships_resolve(): void
    {
        $party = Party::factory()->create(['display_name_en' => 'Rel Party']);
        $identity = new PartyIdentity(['party_id' => $party->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $identity->setIdentityNumber('SIM-777001');
        $identity->save();

        $plot = Plot::factory()->create();
        $ownership = PlotOwnership::factory()->create([
            'plot_id' => $plot->id,
            'party_id' => $party->id,
        ]);

        $this->assertSame($party->id, $identity->party->id);
        $this->assertSame($plot->id, $ownership->plot->id);
        $this->assertSame($party->id, $ownership->party->id);
        $this->assertSame(1, $party->identities()->count());
        $this->assertSame(1, $party->ownerships()->count());
        $this->assertSame($identity->id, $party->primaryIdentity()->id);
        $this->assertSame($ownership->id, $plot->currentOwnerships()->first()->id);
    }

    public function test_plot_ties_into_the_phase_one_geo_hierarchy(): void
    {
        $plot = Plot::factory()->create();

        $this->assertInstanceOf(Mouza::class, $plot->mouza);
        $this->assertInstanceOf(Upazila::class, $plot->mouza->upazila);
        $this->assertSame($plot->mouza->upazila->district->id, $plot->mouza->upazila->district->id);
    }

    public function test_roles_attach_to_users_without_touching_the_users_table(): void
    {
        $user = \App\Models\User::factory()->create();
        $role = Role::factory()->named(Role::REGISTRY_OFFICER)->create();

        $user->roles()->attach($role->id);

        $this->assertTrue($user->fresh()->hasRole(Role::REGISTRY_OFFICER));
        $this->assertFalse($user->fresh()->hasRole(Role::ADMIN));
    }

    public function test_phase_three_migrations_are_reversible_without_touching_phase_one_and_two(): void
    {
        Artisan::call('migrate:rollback', ['--step' => 6]);

        foreach (['roles', 'role_user', 'parties', 'party_identities', 'plots', 'plot_ownerships', 'sensitive_read_audits'] as $table) {
            $this->assertFalse(Schema::hasTable($table), "$table should be dropped on rollback");
        }

        foreach (['users', 'districts', 'upazilas', 'mouzas', 'cache', 'jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table must survive rollback");
        }

        Artisan::call('migrate');

        foreach (['roles', 'parties', 'party_identities', 'plots', 'plot_ownerships', 'sensitive_read_audits'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table should be recreated");
        }
    }
}
