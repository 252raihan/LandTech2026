<?php

namespace App\Http\Requests\Citizen;

use App\Models\LandRecord;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Phase 5 — validation for updating a land record.
 *
 * Extends the store rules rather than duplicating them, so the two paths cannot
 * drift apart. Authorization is left to the controller, which has already
 * resolved an OWNER-SCOPED record and checked the policy against it — the
 * request has no way to know which record is being edited.
 */
class UpdateLandRecordRequest extends StoreLandRecordRequest
{
    public function authorize(): bool
    {
        // The controller performs the ownership + lifecycle check against the
        // resolved record. Returning true here avoids a second, weaker rule.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return parent::rules();
    }

    /**
     * Same allow-list as create. `status`, `party_id` and `reviewed_by` remain
     * unreachable from the client.
     *
     * @return array<string, mixed>
     */
    public function recordAttributes(): array
    {
        return parent::recordAttributes();
    }
}
