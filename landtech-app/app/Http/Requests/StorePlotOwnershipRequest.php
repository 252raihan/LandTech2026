<?php

namespace App\Http\Requests;

use App\Models\PlotOwnership;
use App\Rules\ShareFraction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Phase 3 — create an ownership record linking a party to a plot.
 *
 * Validates the exact-fraction share and the temporal sanity of the record:
 * a disposal can never predate an acquisition, and a record with a disposal
 * date cannot claim "current" status.
 */
class StorePlotOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Registry officers and admins maintain ownership records.
        return $this->user()?->hasAnyRole([
            \App\Models\Role::ADMIN,
            \App\Models\Role::REGISTRY_OFFICER,
        ]) ?? false;
    }

    public function rules(): array
    {
        return [
            'plot_id' => ['required', 'integer', Rule::exists('plots', 'id')],
            'party_id' => ['required', 'integer', Rule::exists('parties', 'id')],

            'ownership_role' => ['required', Rule::in([
                PlotOwnership::ROLE_SOLE,
                PlotOwnership::ROLE_JOINT,
                PlotOwnership::ROLE_HEIR_SHARE,
            ])],

            'share_numerator' => ['required', 'integer', 'min:1'],
            'share_denominator' => [
                'required',
                'integer',
                'min:1',
                // Cross-checks the pair and rejects shares greater than 1.
                new ShareFraction(
                    $this->input('share_numerator'),
                    $this->input('share_denominator'),
                ),
            ],

            'acquired_on' => ['nullable', 'date'],
            'disposed_on' => ['nullable', 'date', 'after_or_equal:acquired_on'],

            'source_deed_no' => ['nullable', 'string', 'max:64'],

            'status' => ['required', Rule::in([
                PlotOwnership::STATUS_CURRENT,
                PlotOwnership::STATUS_HISTORICAL,
                PlotOwnership::STATUS_DISPUTED,
            ])],
        ];
    }

    /**
     * Cross-field checks. Reads from the VALIDATOR's data (not $this->input())
     * so behaviour is identical under framework validation and standalone
     * Validator instances in tests.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            // A disposed record cannot still be flagged current.
            if (
                ! empty($data['disposed_on'])
                && ($data['status'] ?? null) === PlotOwnership::STATUS_CURRENT
            ) {
                $validator->errors()->add(
                    'status',
                    'A disposed ownership record cannot have the status "current".'
                );
            }

            // A sole ownership must be a whole share.
            if (
                ($data['ownership_role'] ?? null) === PlotOwnership::ROLE_SOLE
                && (int) ($data['share_numerator'] ?? 0) !== (int) ($data['share_denominator'] ?? 0)
            ) {
                $validator->errors()->add(
                    'share_numerator',
                    'A sole ownership must hold the whole plot (share 1/1).'
                );
            }
        });
    }

    public function attributes(): array
    {
        return [
            'share_numerator' => 'share numerator',
            'share_denominator' => 'share denominator',
            'acquired_on' => 'acquisition date',
            'disposed_on' => 'disposal date',
        ];
    }
}
