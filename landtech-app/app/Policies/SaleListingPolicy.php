<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\SaleListing;
use App\Models\User;

/**
 * Phase 5 — authorization for sale listings.
 *
 * THE CRITICAL RULE
 *   `setVerificationStatus` is reviewer-only. No owner path exists to it, at any
 *   privilege level within the citizen role. This is what stops a citizen
 *   marking their own land "verified".
 *
 *   Likewise `publish` requires the listing to have passed review; an owner
 *   cannot publish directly, and cannot mark a listing `sold` without the
 *   workflow that supports it.
 */
class SaleListingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN) || $this->canReview($user);
    }

    public function view(User $user, SaleListing $listing): bool
    {
        if ($this->owns($user, $listing)) {
            return true;
        }

        if ($this->canReview($user)) {
            return true;
        }

        // Any authenticated user may view a PUBLISHED listing — that is the
        // point of publishing. Nothing else is visible.
        return $listing->isPublished();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN);
    }

    public function update(User $user, SaleListing $listing): bool
    {
        return $this->owns($user, $listing) && $listing->isEditableByOwner();
    }

    public function delete(User $user, SaleListing $listing): bool
    {
        return $this->owns($user, $listing)
            && in_array($listing->status, [SaleListing::STATUS_DRAFT, SaleListing::STATUS_WITHDRAWN], true);
    }

    /**
     * Submitting for review is an owner action on their own draft.
     */
    public function submit(User $user, SaleListing $listing): bool
    {
        return $this->owns($user, $listing)
            && $listing->status === SaleListing::STATUS_DRAFT;
    }

    /**
     * An owner may withdraw a listing that is already live.
     */
    public function withdraw(User $user, SaleListing $listing): bool
    {
        return $this->owns($user, $listing)
            && in_array($listing->status, [
                SaleListing::STATUS_PUBLISHED,
                SaleListing::STATUS_PENDING_REVIEW,
                SaleListing::STATUS_RESERVED,
            ], true);
    }

    /**
     * Reviewer-only. There is deliberately no owner path to this ability.
     */
    public function review(User $user, SaleListing $listing): bool
    {
        if (! $this->canReview($user)) {
            return false;
        }

        return $listing->user_id !== $user->id;
    }

    /**
     * Reviewer-only. Setting verification is how the "verified" label is earned.
     */
    public function setVerificationStatus(User $user, SaleListing $listing): bool
    {
        return $this->review($user, $listing);
    }

    /**
     * Reviewer-only. Publication follows review; an owner cannot self-publish.
     */
    public function publish(User $user, SaleListing $listing): bool
    {
        return $this->review($user, $listing);
    }

    /**
     * Marking a listing SOLD is a reviewer/registry action, not an owner one:
     * a sale has legal consequences this workflow does not implement.
     */
    public function markSold(User $user, SaleListing $listing): bool
    {
        return $this->review($user, $listing);
    }

    protected function owns(User $user, SaleListing $listing): bool
    {
        return $listing->user_id === $user->id;
    }

    protected function canReview(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER]);
    }
}
