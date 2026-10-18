<?php

namespace App\Policies;

use App\Models\BuyerInterestRequest;
use App\Models\Role;
use App\Models\User;

/**
 * Phase 5 — authorization for buyer interest requests.
 *
 * VISIBILITY
 *   Only two parties may read an interest request:
 *     1. the BUYER who raised it, and
 *     2. the OWNER of the listing it targets.
 *   Plus a reviewer/admin, for dispute handling.
 *
 *   No other citizen can see it — not by ID, not by listing, not by search. This
 *   is what keeps one buyer's enquiry and the owner's reply private.
 *
 * A buyer can never mutate the listing or the land record through this object.
 */
class BuyerInterestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN) || $this->canReview($user);
    }

    public function view(User $user, BuyerInterestRequest $interest): bool
    {
        if ($this->isBuyer($user, $interest)) {
            return true;
        }

        if ($this->isListingOwner($user, $interest)) {
            return true;
        }

        return $this->canReview($user);
    }

    /**
     * Only a citizen who is NOT the listing owner may express interest in it —
     * a seller cannot open an enquiry against their own listing.
     */
    public function create(User $user, ?int $listingOwnerId = null): bool
    {
        if (! $user->hasRole(Role::CITIZEN)) {
            return false;
        }

        return $listingOwnerId === null || $listingOwnerId !== $user->id;
    }

    /**
     * Only the LISTING OWNER may respond — that is how the seller decides
     * whether to acknowledge, share contact or decline.
     */
    public function respond(User $user, BuyerInterestRequest $interest): bool
    {
        return $this->isListingOwner($user, $interest);
    }

    /**
     * A buyer may withdraw their own enquiry, but cannot edit the seller's reply.
     */
    public function withdraw(User $user, BuyerInterestRequest $interest): bool
    {
        return $this->isBuyer($user, $interest);
    }

    public function delete(User $user, BuyerInterestRequest $interest): bool
    {
        return false; // Retained as part of the enquiry history.
    }

    protected function isBuyer(User $user, BuyerInterestRequest $interest): bool
    {
        return $interest->user_id === $user->id;
    }

    protected function isListingOwner(User $user, BuyerInterestRequest $interest): bool
    {
        return $interest->listing?->user_id === $user->id;
    }

    protected function canReview(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER]);
    }
}
