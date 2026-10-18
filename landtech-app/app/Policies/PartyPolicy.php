<?php

namespace App\Policies;

use App\Models\Party;
use App\Models\Role;
use App\Models\User;

/**
 * Server-side authorization for Party records.
 *
 * These checks are authoritative. UI hiding is cosmetic only — every sensitive
 * read must pass through a policy method here, and the identity-level reads are
 * additionally gated by PartyIdentityPolicy and recorded by the audit chain.
 *
 * Role matrix:
 *   admin            — full access, including create/update/delete
 *   registry_officer — read + maintain party records
 *   bank_officer     — read-only (lien/CIB due diligence)
 *   citizen          — read only their own linked party record, and only when
 *                      that link has been VERIFIED by a reviewer (Phase 5)
 */
class PartyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN,
            Role::REGISTRY_OFFICER,
            Role::BANK_OFFICER,
        ]);
    }

    public function view(User $user, Party $party): bool
    {
        if ($user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER, Role::BANK_OFFICER])) {
            return true;
        }

        return $this->owns($user, $party);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER]);
    }

    public function update(User $user, Party $party): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER]);
    }

    public function delete(User $user, Party $party): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    /**
     * May this user export party data in bulk?
     * Restricted to admin — exports are audited separately.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    /**
     * A citizen may read a party record when that party is the user's own.
     *
     * PHASE 5: this now resolves through `party_user_links`, and ONLY through
     * links whose status is `verified`. A pending or rejected claim grants
     * nothing, which is precisely what stops a citizen establishing ownership by
     * asserting an NID or Party ID.
     *
     * Before any link is verified this returns false for everyone, preserving the
     * conservative Phase 3 behaviour exactly.
     */
    protected function owns(User $user, Party $party): bool
    {
        return $user->verifiedPartyLinks()
            ->where('party_id', $party->id)
            ->exists();
    }
}
