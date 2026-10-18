<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\VerificationRequest;

/**
 * Phase 5 — authorization for ownership verification requests.
 *
 * SEPARATION OF DUTIES IS THE WHOLE POINT
 *   The requester must never be able to review their own request. This is
 *   enforced structurally here rather than left to a UI convention, so a citizen
 *   cannot self-approve a verification — not by URL, not by request body.
 *
 * A buyer never gains access to a seller's private documents through this
 * workflow. The request stores the buyer's OWN reference data; reading a
 * seller's uploads still requires LandDocumentPolicy, which only the owner and a
 * reviewer pass.
 */
class VerificationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN) || $this->canReview($user);
    }

    public function view(User $user, VerificationRequest $request): bool
    {
        if ($this->owns($user, $request)) {
            return true;
        }

        return $this->canReview($user);
    }

    /**
     * Any authenticated citizen may request verification for any land
     * reference — including land that has no listing.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN);
    }

    /**
     * The requester may add information while the request is still open.
     * Land identifiers are frozen once submitted: they are the reference the
     * reviewer worked from.
     */
    public function update(User $user, VerificationRequest $request): bool
    {
        return $this->owns($user, $request)
            && $request->status === VerificationRequest::STATUS_ADDITIONAL_INFO_REQUIRED;
    }

    public function delete(User $user, VerificationRequest $request): bool
    {
        return false; // Requests are part of the audit trail and are retained.
    }

    /**
     * Only a reviewer may change the outcome — and never on their own request.
     */
    public function review(User $user, VerificationRequest $request): bool
    {
        if (! $this->canReview($user)) {
            return false;
        }

        return $request->user_id !== $user->id;
    }

    /**
     * Recording individual check results is part of reviewing.
     */
    public function recordCheck(User $user, VerificationRequest $request): bool
    {
        return $this->review($user, $request);
    }

    /**
     * Attaching evidence to a request requires ownership of the request.
     */
    public function attachEvidence(User $user, VerificationRequest $request): bool
    {
        return $this->owns($user, $request);
    }

    protected function owns(User $user, VerificationRequest $request): bool
    {
        return $request->user_id === $user->id;
    }

    protected function canReview(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER]);
    }
}
