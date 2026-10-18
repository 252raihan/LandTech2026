<?php

namespace App\Policies;

use App\Models\LandDocument;
use App\Models\Role;
use App\Models\User;

/**
 * Phase 5 — authorization for private land documents.
 *
 * This policy is the ONLY gate in front of file bytes. Every download and
 * preview goes through it, so a document can never be fetched by guessing a
 * path or an ID.
 *
 * RULES
 *   - A citizen may see only their OWN uploads. No cross-user read.
 *   - A citizen may delete only their own document while it is still pending.
 *     Once a reviewer has acted, the file is evidence and is retained.
 *   - Only a reviewer may change `review_status`.
 *   - A reviewer may view documents attached to records they can review, but the
 *     citizen-to-citizen path is closed entirely.
 */
class LandDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN) || $this->canReview($user);
    }

    public function view(User $user, LandDocument $document): bool
    {
        if ($this->owns($user, $document)) {
            return true;
        }

        // Reviewers may read evidence, but only for documents they are entitled
        // to review (never a self-submitted one).
        return $this->canReview($user) && $document->user_id !== $user->id;
    }

    /**
     * Downloading is a distinct, auditable action from viewing a metadata row.
     */
    public function download(User $user, LandDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN);
    }

    /**
     * Metadata may be corrected by the owner while still pending.
     */
    public function update(User $user, LandDocument $document): bool
    {
        return $this->owns($user, $document) && $document->isPending();
    }

    public function delete(User $user, LandDocument $document): bool
    {
        return $this->owns($user, $document) && $document->isPending();
    }

    /**
     * Approving, rejecting or requesting a replacement is reviewer-only.
     */
    public function review(User $user, LandDocument $document): bool
    {
        if (! $this->canReview($user)) {
            return false;
        }

        return $document->user_id !== $user->id;
    }

    protected function owns(User $user, LandDocument $document): bool
    {
        return $document->user_id === $user->id;
    }

    protected function canReview(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER]);
    }
}
