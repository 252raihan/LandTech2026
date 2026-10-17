<?php

namespace App\Policies;

use App\Models\PartyIdentity;
use App\Models\Role;
use App\Models\User;

/**
 * Server-side authorization for identity material (NID and friends).
 *
 * This is the last line of defence before a real NID value is decrypted, so the
 * defaults are strict:
 *
 *   viewMasked  — several roles; masked output carries no identity risk.
 *   viewFull    — admin + registry_officer ONLY. Every call must be audited.
 *   searchByNid — admin + registry_officer ONLY. Every call must be audited.
 *   export      — admin ONLY. Every call must be audited.
 *
 * Bank officers deliberately CANNOT read full NIDs: they need lien status, not
 * identity numbers.
 */
class PartyIdentityPolicy
{
    /**
     * Masked display (•• 1234) — safe for anyone who may view the party.
     */
    public function viewMasked(User $user, PartyIdentity $identity): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN,
            Role::REGISTRY_OFFICER,
            Role::BANK_OFFICER,
        ]);
    }

    /**
     * Full, decrypted NID. High privilege — MUST be accompanied by an audit entry.
     */
    public function viewFull(User $user, PartyIdentity $identity): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN,
            Role::REGISTRY_OFFICER,
        ]);
    }

    /**
     * Lookup by identity number. High privilege — MUST be audited.
     */
    public function searchByNid(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN,
            Role::REGISTRY_OFFICER,
        ]);
    }

    /**
     * Bulk export of identity data. Admin only — MUST be audited.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(Role::ADMIN);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN,
            Role::REGISTRY_OFFICER,
        ]);
    }

    public function update(User $user, PartyIdentity $identity): bool
    {
        return $user->hasAnyRole([
            Role::ADMIN,
            Role::REGISTRY_OFFICER,
        ]);
    }
}
