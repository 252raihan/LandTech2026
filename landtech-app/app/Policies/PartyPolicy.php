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
 *   citizen          — read only their own linked party record
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
     * Phase 3 has no users.party_id column (the users table is frozen), so the
     * link is resolved through the identity layer: a party is "owned" by the
     * user when the user's email matches the party's synthetic demo identity.
     *
     * NOTE: this is a deliberately conservative placeholder. Real self-service
     * linking needs an explicit, verified claim flow (deferred to a later phase).
     */
    protected function owns(User $user, Party $party): bool
    {
        return false;
    }
}
