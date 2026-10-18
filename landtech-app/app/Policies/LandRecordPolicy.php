<?php

namespace App\Policies;

use App\Models\LandRecord;
use App\Models\Role;
use App\Models\User;

/**
 * Phase 5 — authorization for citizen land records.
 *
 * RULES
 *   - A citizen may only ever see and mutate their OWN records. There is no
 *     cross-user read path, so IDOR on `land_records` is structurally closed.
 *   - A citizen cannot review, verify or reject their own record. Only a
 *     registry officer or admin may review, and never the submitter.
 *   - Editing is blocked once the record has entered the review pipeline, so a
 *     user cannot quietly change land details after submitting them.
 */
class LandRecordPolicy
{
    /**
     * Citizen panel list — always scoped, never a blanket allow.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN);
    }

    public function view(User $user, LandRecord $record): bool
    {
        // Owner sees their own record.
        if ($this->owns($user, $record)) {
            return true;
        }

        // Reviewers may see submissions in order to review them.
        return $this->canReview($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::CITIZEN);
    }

    /**
     * Only the owner may update, and only while the record is still editable.
     * A reviewer must never edit the claimant's assertions — they record a
     * decision instead.
     */
    public function update(User $user, LandRecord $record): bool
    {
        return $this->owns($user, $record) && $record->isEditableByOwner();
    }

    /**
     * The owner may request deletion only while the record is still a draft.
     * Once submitted, the record is part of the evidence trail and is retained.
     */
    public function delete(User $user, LandRecord $record): bool
    {
        return $this->owns($user, $record) && $record->status === LandRecord::STATUS_DRAFT;
    }

    /**
     * Submitting for review is an owner action on their own draft.
     */
    public function submit(User $user, LandRecord $record): bool
    {
        return $this->owns($user, $record)
            && in_array($record->status, [
                LandRecord::STATUS_DRAFT,
                LandRecord::STATUS_CORRECTION_REQUESTED,
                LandRecord::STATUS_ADDITIONAL_INFO_REQUIRED,
            ], true);
    }

    /**
     * The owner may raise a correction request against a submitted record.
     */
    public function requestCorrection(User $user, LandRecord $record): bool
    {
        return $this->owns($user, $record)
            && ! in_array($record->status, [
                LandRecord::STATUS_DRAFT,
                LandRecord::STATUS_CORRECTION_REQUESTED,
            ], true);
    }

    /**
     * Attaching a document to a record requires ownership of both.
     */
    public function attachDocument(User $user, LandRecord $record): bool
    {
        return $this->owns($user, $record);
    }

    /**
     * Only a reviewer may change the verification outcome — and never their own
     * submission (see `review`, which enforces the separation of duties).
     */
    public function review(User $user, LandRecord $record): bool
    {
        if (! $this->canReview($user)) {
            return false;
        }

        // Separation of duties: nobody reviews their own submission.
        return $record->user_id !== $user->id;
    }

    protected function owns(User $user, LandRecord $record): bool
    {
        return $record->user_id === $user->id;
    }

    protected function canReview(User $user): bool
    {
        return $user->hasAnyRole([Role::ADMIN, Role::REGISTRY_OFFICER]);
    }
}
