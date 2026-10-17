<?php

namespace App\Services;

use App\Models\Party;
use App\Models\PartyIdentity;
use App\Models\Role;
use App\Models\SensitiveReadAudit;
use App\Models\User;
use App\Support\Audit\AuditChain;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * The ONLY sanctioned entry point for reading identity material.
 *
 * Each method enforces three things in order:
 *   1. AUTHORIZE — a policy check against the acting user (server-side, always).
 *   2. ACT       — perform the masked / full / search / export operation.
 *   3. AUDIT     — append an immutable, hash-chained record with actor, purpose,
 *                  subject and action.
 *
 * Nothing else in the application should call PartyIdentity::reveal() directly.
 */
class IdentityAccessService
{
    public function __construct(
        protected AuditChain $chain,
        protected ?Request $request = null,
    ) {
        $this->request ??= request();
    }

    /**
     * Masked read. Safe by default, and still audited — so probing patterns are
     * visible even when no plaintext NID is ever disclosed.
     */
    public function viewMasked(User $actor, PartyIdentity $identity, ?string $purpose = null): string
    {
        $this->ensure(Gate::forUser($actor)->allows('viewMasked', $identity));

        $masked = $identity->maskedNumber();

        $this->audit($actor, SensitiveReadAudit::ACTION_VIEW_MASKED, $identity, $purpose);

        return $masked;
    }

    /**
     * Full NID read. Requires an explicit purpose so the audit trail explains
     * WHY the plaintext was disclosed, not merely that it was.
     */
    public function viewFull(User $actor, PartyIdentity $identity, string $purpose): string
    {
        $this->ensure(Gate::forUser($actor)->allows('viewFull', $identity));
        $this->ensurePurpose($purpose);

        $value = $identity->reveal();

        $this->audit($actor, SensitiveReadAudit::ACTION_VIEW_FULL, $identity, $purpose);

        return $value;
    }

    /**
     * Look up identities by raw identity number.
     *
     * Uses the keyed HMAC — never a plain hash and never a LIKE over ciphertext.
     * Candidate hashes cover every accepted key generation so the search keeps
     * working while a key rotation is in flight.
     *
     * @return Collection<int, PartyIdentity>
     */
    public function searchByNid(User $actor, string $rawNumber, string $purpose): Collection
    {
        $this->ensure(Gate::forUser($actor)->allows('searchByNid', PartyIdentity::class));
        $this->ensurePurpose($purpose);

        $hashes = array_values(PartyIdentity::lookupHashesFor($rawNumber));

        $matches = PartyIdentity::query()
            ->whereIn('id_number_lookup_hmac', $hashes)
            ->get();

        // One audit row per search, not per hit — records the attempt itself.
        $this->audit(
            $actor,
            SensitiveReadAudit::ACTION_SEARCH_NID,
            null,
            $purpose,
            $matches->first()?->party_id,
        );

        return $matches;
    }

    /**
     * Bulk export of identity data. Admin only, always audited.
     *
     * @return Collection<int, PartyIdentity>
     */
    public function export(User $actor, string $purpose): Collection
    {
        $this->ensure(Gate::forUser($actor)->allows('export', PartyIdentity::class));
        $this->ensurePurpose($purpose);

        $rows = PartyIdentity::query()->with('party')->get();

        $this->audit($actor, SensitiveReadAudit::ACTION_EXPORT, null, $purpose);

        return $rows;
    }

    /**
     * Record a masked read for an entire party (one audit row, party-scoped).
     */
    public function viewPartyIdentitiesMasked(User $actor, Party $party, ?string $purpose = null): array
    {
        $this->ensure(Gate::forUser($actor)->allows('view', $party));

        $out = [];

        foreach ($party->identities as $identity) {
            if (Gate::forUser($actor)->allows('viewMasked', $identity)) {
                $out[] = [
                    'type' => $identity->id_type,
                    'masked' => $identity->maskedNumber(),
                    'is_primary' => $identity->is_primary,
                ];
            }
        }

        $this->audit($actor, SensitiveReadAudit::ACTION_VIEW_MASKED, $party->primaryIdentity(), $purpose, $party->id);

        return $out;
    }

    protected function audit(
        User $actor,
        string $action,
        ?PartyIdentity $identity,
        ?string $purpose = null,
        ?int $subjectPartyId = null,
    ): SensitiveReadAudit {
        return $this->chain->append([
            'user_id' => $actor->id,
            'action' => $action,
            'purpose' => $purpose,
            'subject_party_id' => $subjectPartyId ?? $identity?->party_id,
            'subject_identity_id' => $identity?->id,
            'ip_address' => $this->request?->ip(),
            'user_agent' => substr((string) $this->request?->userAgent(), 0, 191),
        ]);
    }

    protected function ensure(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    protected function ensurePurpose(string $purpose): void
    {
        if (trim($purpose) === '') {
            throw new AuthorizationException('A purpose is required for sensitive identity access.');
        }
    }
}
