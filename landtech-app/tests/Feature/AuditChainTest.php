<?php

namespace Tests\Feature;

use App\Models\SensitiveReadAudit;
use App\Models\User;
use App\Support\Audit\AuditChain;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3 — append-only, hash-chained audit log integrity.
 *
 * These tests verify that the chain detects tampering. They do NOT claim the
 * chain is tamper-PROOF — see the limitations documented on AuditChain and the
 * dedicated limitation test at the bottom of this file.
 */
class AuditChainTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function chain(): AuditChain
    {
        return app(AuditChain::class);
    }

    protected function entry(array $overrides = []): SensitiveReadAudit
    {
        return $this->chain()->append(array_merge([
            'user_id' => null,
            'action' => SensitiveReadAudit::ACTION_VIEW_MASKED,
            'purpose' => 'test',
        ], $overrides));
    }

    public function test_first_entry_anchors_to_genesis(): void
    {
        $first = $this->entry();

        $this->assertSame(1, $first->chain_position);
        $this->assertSame(AuditChain::GENESIS_HASH, $first->prev_hash);
        $this->assertNotSame(AuditChain::GENESIS_HASH, $first->record_hash);
        $this->assertSame(64, strlen($first->record_hash));
    }

    public function test_entries_link_to_their_predecessor(): void
    {
        $a = $this->entry(['purpose' => 'first']);
        $b = $this->entry(['purpose' => 'second']);
        $c = $this->entry(['purpose' => 'third']);

        $this->assertSame($a->record_hash, $b->prev_hash);
        $this->assertSame($b->record_hash, $c->prev_hash);
        $this->assertSame([1, 2, 3], [$a->chain_position, $b->chain_position, $c->chain_position]);
    }

    public function test_a_valid_chain_verifies(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->entry(['purpose' => "entry {$i}"]);
        }

        $result = $this->chain()->verify();

        $this->assertTrue($result['valid'], $result['reason'] ?? '');
        $this->assertSame(5, $result['checked']);
        $this->assertNull($result['broken_at']);
    }

    public function test_editing_a_row_breaks_the_chain_and_is_detected(): void
    {
        $this->entry(['purpose' => 'honest']);
        $target = $this->entry(['purpose' => 'original']);
        $this->entry(['purpose' => 'later']);

        // Tamper directly in the database, bypassing the model.
        DB::table('sensitive_read_audits')
            ->where('id', $target->id)
            ->update(['purpose' => 'FALSIFIED']);

        $result = $this->chain()->verify();

        $this->assertFalse($result['valid']);
        $this->assertSame($target->chain_position, $result['broken_at']);
        $this->assertStringContainsString('record_hash mismatch', $result['reason']);
    }

    public function test_deleting_a_row_breaks_the_chain_and_is_detected(): void
    {
        $this->entry(['purpose' => 'a']);
        $middle = $this->entry(['purpose' => 'b']);
        $this->entry(['purpose' => 'c']);

        DB::table('sensitive_read_audits')->where('id', $middle->id)->delete();

        $result = $this->chain()->verify();

        $this->assertFalse($result['valid']);
        $this->assertSame('chain_position gap or reorder', $result['reason']);
    }

    public function test_chain_position_uniqueness_prevents_forks(): void
    {
        $first = $this->entry();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        // Force a duplicate position — simulates a concurrent append racing.
        DB::table('sensitive_read_audits')->insert([
            'user_id' => null,
            'action' => SensitiveReadAudit::ACTION_VIEW_MASKED,
            'purpose' => 'fork attempt',
            'chain_position' => $first->chain_position,
            'prev_hash' => AuditChain::GENESIS_HASH,
            'record_hash' => str_repeat('f', 64),
            'created_at' => now(),
        ]);
    }

    public function test_audit_rows_are_never_updated_by_the_model(): void
    {
        $entry = $this->entry(['purpose' => 'immutable']);

        // Eloquent must not attempt to write updated_at (the column does not exist).
        $this->assertNull(SensitiveReadAudit::UPDATED_AT);

        $columns = array_map(fn($c) => $c['name'], \Illuminate\Support\Facades\Schema::getColumns('sensitive_read_audits'));
        $this->assertNotContains('updated_at', $columns);
    }

    public function test_documented_limitation_full_table_replacement_is_not_detectable(): void
    {
        // This test ENCODES THE KNOWN LIMITATION rather than pretending it away.
        //
        // A DB-backed hash chain is tamper-EVIDENT, not tamper-PROOF: an actor
        // with direct write access can rebuild the whole chain from genesis, and
        // verify() will report it as valid. There is no external anchor.
        $this->entry(['purpose' => 'original one']);
        $this->entry(['purpose' => 'original two']);

        DB::table('sensitive_read_audits')->delete();

        $chain = $this->chain();
        $chain->append(['action' => SensitiveReadAudit::ACTION_VIEW_FULL, 'purpose' => 'forged one']);
        $chain->append(['action' => SensitiveReadAudit::ACTION_VIEW_FULL, 'purpose' => 'forged two']);

        $result = $chain->verify();

        // A fully rewritten chain still verifies — hence the documented caveat
        // that production needs WORM storage plus an external trust anchor.
        $this->assertTrue($result['valid']);
        $this->assertSame(2, $result['checked']);
    }

    public function test_actor_purpose_subject_and_action_are_recorded(): void
    {
        $user = User::factory()->create();
        $party = \App\Models\Party::factory()->create();
        $identity = new \App\Models\PartyIdentity([
            'party_id' => $party->id,
            'id_type' => \App\Models\PartyIdentity::TYPE_NID,
        ]);
        $identity->setIdentityNumber('SIM-AUDIT-01');
        $identity->save();

        $entry = $this->entry([
            'user_id' => $user->id,
            'action' => SensitiveReadAudit::ACTION_VIEW_FULL,
            'purpose' => 'mutation registration',
            'subject_party_id' => $party->id,
            'subject_identity_id' => $identity->id,
        ]);

        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame('view_full', $entry->action);
        $this->assertSame('mutation registration', $entry->purpose);
        $this->assertSame($party->id, $entry->subject_party_id);
        $this->assertSame($identity->id, $entry->subject_identity_id);
        $this->assertNotNull($entry->created_at);
    }
}
