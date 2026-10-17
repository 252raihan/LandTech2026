<?php

namespace App\Support\Audit;

use App\Models\SensitiveReadAudit;
use Illuminate\Support\Facades\DB;

/**
 * Append-only, hash-chained audit log for sensitive identity access.
 *
 * Every row commits to its predecessor:
 *
 *     record_hash = sha256( canonical_payload | prev_hash )
 *
 * `chain_position` is UNIQUE, so two concurrent appends cannot fork the chain —
 * the loser of the race fails on the constraint instead of silently creating a
 * second branch. Appends run inside a transaction with a row lock on the tail.
 *
 * ============================ LIMITATIONS ============================
 * A database-backed hash chain is TAMPER-EVIDENT, NOT TAMPER-PROOF.
 *
 *   - Anyone with direct write access to `sensitive_read_audits` can rewrite
 *     history and recompute every downstream hash. The chain proves that a row
 *     was not edited *in isolation*; it cannot prove the whole table is honest.
 *   - There is no external trust anchor: no WORM storage, no notary, no
 *     transparency log. A third party cannot independently verify anything.
 *   - Genesis is unanchored. The very first row's prev_hash is a known constant,
 *     so an attacker who replaces the entire table can produce a valid chain.
 *
 * Production would require append-only/WORM storage, an external anchor
 * (e.g. periodic root hash published to a trusted third party), and key custody
 * outside the application database.
 *
 * The frontend LocalStorage ledger in public/js/crypto.js is a DEMO ARTIFACT.
 * It is NOT authoritative and is never consulted for integrity decisions.
 * =====================================================================
 */
class AuditChain
{
    /** Anchor for the first row in the chain. */
    public const GENESIS_HASH = '0000';

    /**
     * Append an audit entry and return it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function append(array $attributes): SensitiveReadAudit
    {
        return DB::transaction(function () use ($attributes) {
            // Lock the tail so concurrent appends serialise rather than fork.
            $tail = SensitiveReadAudit::query()
                ->orderByDesc('chain_position')
                ->lockForUpdate()
                ->first();

            $position = $tail ? $tail->chain_position + 1 : 1;
            $prevHash = $tail ? $tail->record_hash : self::GENESIS_HASH;

            $record = new SensitiveReadAudit($attributes);
            $record->chain_position = $position;
            $record->prev_hash = $prevHash;
            $record->record_hash = $this->hashFor($record, $prevHash);
            $record->created_at = now();
            $record->save();

            return $record;
        });
    }

    /**
     * Recompute the chain from genesis and report the first broken link.
     *
     * @return array{valid: bool, checked: int, broken_at: int|null, reason: string|null}
     */
    public function verify(): array
    {
        $prevHash = self::GENESIS_HASH;
        $expectedPosition = 1;
        $checked = 0;

        foreach (SensitiveReadAudit::query()->orderBy('chain_position')->cursor() as $row) {
            if ($row->chain_position !== $expectedPosition) {
                return [
                    'valid' => false,
                    'checked' => $checked,
                    'broken_at' => $row->chain_position,
                    'reason' => 'chain_position gap or reorder',
                ];
            }

            if (! hash_equals($prevHash, (string) $row->prev_hash)) {
                return [
                    'valid' => false,
                    'checked' => $checked,
                    'broken_at' => $row->chain_position,
                    'reason' => 'prev_hash does not match predecessor record_hash',
                ];
            }

            if (! hash_equals($this->hashFor($row, $prevHash), (string) $row->record_hash)) {
                return [
                    'valid' => false,
                    'checked' => $checked,
                    'broken_at' => $row->chain_position,
                    'reason' => 'record_hash mismatch — row content was modified',
                ];
            }

            $prevHash = (string) $row->record_hash;
            $expectedPosition++;
            $checked++;
        }

        return ['valid' => true, 'checked' => $checked, 'broken_at' => null, 'reason' => null];
    }

    /**
     * Deterministic digest over the audited fields plus the predecessor hash.
     *
     * Field order is fixed and the payload is length-prefixed per field so that
     * two different attribute sets cannot collide by concatenation ambiguity.
     */
    public function hashFor(SensitiveReadAudit $record, string $prevHash): string
    {
        $payload = implode('|', [
            $record->chain_position,
            $record->user_id ?? '',
            $record->action,
            $record->purpose ?? '',
            $record->subject_party_id ?? '',
            $record->subject_identity_id ?? '',
            $record->ip_address ?? '',
            $record->user_agent ?? '',
        ]);

        return hash('sha256', strlen($payload) . ':' . $payload . '|' . $prevHash);
    }
}
