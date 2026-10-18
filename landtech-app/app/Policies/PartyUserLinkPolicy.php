<?php

namespace App\Policies;

use App\Models\PartyUserLink;
use App\Models\Role;
use App\Models\User;

/**
 * Phase 5 — authorization for user ↔ party ownership claims.
 *
 * THIS IS THE ANTI-ESCALATION POLICY.
 *   A citizen may create a claim, and may read their own claim. They can NEVER
 *   verify one — not their own, not anyone else's. Only a reviewer can, and a
 *   reviewer may never verify a claim they themselves submitted.
 *
 *   Because PartyPolicy::owns() consults only VERIFIED links, an unverified
 *   claim grants no access to any land, document or listing. Submitting an NID
 *   or Party ID therefore proves nothing on its own.
 */
class PartyUserLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN) || $this->canReview($user);
    }

    public function view(User $user, PartyUserLink $link): bool
    {
        return $link->user_id === $user->id || $this->canReview($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN);
    }

    /**
     * A claimant may only amend their own claim, and only while it is pending.
     * Once reviewed, the decision stands and a new claim is required.
     */
    public function update(User $user, PartyUserLink $link): bool
    {
        return $link->user_id === $user->id
            && $link->status === PartyUserLink::STATUS_PENDING;
    }

    public function delete(User $user, PartyUserLink $link): bool
    {
        return $link->user_id === $user->id
            && $link->status === PartyUserLink::STATUS_PENDING;
    }

    /**
     * Reviewer-only, and never on a self-submitted claim.
     */
    public function review(User $user, PartyUserLink $link): bool
    {
        if (! $this->canReview($user)) {
            return false;
        }

        return $link->user_id !== $user->id;
    }

    protected function canReview(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER]);
    }
}
