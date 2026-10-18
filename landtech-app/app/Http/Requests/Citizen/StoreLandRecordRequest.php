<?php

namespace App\Http\Requests\Citizen;

use App\Models\LandRecord;
use App\Models\Mouza;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Phase 5 — validation for creating a land record.
 *
 * `authorize()` defers to the policy rather than duplicating the role check, so
 * there is exactly one definition of "may create a land record".
 *
 * Note what is NOT accepted: `user_id`, `status`, `party_id`, `reviewed_by` and
 * `plot_id`. Those are set server-side. A client cannot submit a record
 * pre-marked verified, nor attribute it to another user or party.
 */
class StoreLandRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LandRecord::class) ?? false;
    }

    public function rules(): array
    {
        return [
            // ---- Location ----
            'mouza_id' => ['nullable', 'integer', Rule::exists('mouzas', 'id')],
            'district_name' => ['nullable', 'string', 'max:128'],
            'upazila_name' => ['nullable', 'string', 'max:128'],
            'mouza_name' => ['nullable', 'string', 'max:128'],
            'jl_no' => ['nullable', 'string', 'max:32'],

            // ---- Land identifiers ----
            'survey_type' => ['nullable', Rule::in(LandRecord::SURVEY_TYPES)],
            'khatian_no' => ['required', 'string', 'max:64'],
            'dag_no' => ['required', 'string', 'max:64'],
            'area' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'area_unit' => ['required', Rule::in(LandRecord::AREA_UNITS)],

            // ---- Claimed ownership (an exact fraction, never a percentage) ----
            'claim_share_numerator' => ['required', 'integer', 'min:1', 'max:1000000'],
            'claim_share_denominator' => ['required', 'integer', 'min:1', 'max:1000000', 'gte:claim_share_numerator'],
            'ownership_basis' => ['nullable', Rule::in([
                LandRecord::BASIS_PURCHASE,
                LandRecord::BASIS_INHERITANCE,
                LandRecord::BASIS_GIFT_HEBA,
                LandRecord::BASIS_COURT_DECREE,
                LandRecord::BASIS_SETTLEMENT,
                LandRecord::BASIS_OTHER,
            ])],
            'ownership_basis_other' => ['nullable', 'string', 'max:191', 'required_if:ownership_basis,' . LandRecord::BASIS_OTHER],

            // ---- Deed ----
            'deed_no' => ['nullable', 'string', 'max:64'],
            'deed_date' => ['nullable', 'date', 'before_or_equal:today'],
            'registration_office' => ['nullable', 'string', 'max:191'],

            // ---- Mutation ----
            'mutation_status' => ['nullable', Rule::in([
                LandRecord::MUTATION_NOT_DONE,
                LandRecord::MUTATION_IN_PROGRESS,
                LandRecord::MUTATION_DONE,
                LandRecord::MUTATION_UNKNOWN,
            ])],
            'mutation_no' => ['nullable', 'string', 'max:64'],

            // ---- Intent ----
            'intended_use' => ['required', Rule::in([
                LandRecord::INTENT_RECORD_KEEPING,
                LandRecord::INTENT_SALE_LISTING,
            ])],

            // Draft or submit. Never "verified".
            'action' => ['nullable', Rule::in(['draft', 'submit'])],
        ];
    }

    /**
     * Only the fields a client is permitted to influence.
     *
     * This allow-list is why `status`, `party_id` and `reviewed_by` cannot be
     * smuggled in through the request body.
     *
     * @return array<string, mixed>
     */
    public function recordAttributes(): array
    {
        $data = $this->validated();

        unset($data['action']);

        // Free-text location names are kept only when no mouza is selected, so
        // the record never disagrees with itself.
        if (! empty($data['mouza_id'])) {
            $mouza = Mouza::query()->with('upazila.district')->find($data['mouza_id']);

            if ($mouza) {
                $data['district_name'] = $mouza->upazila?->district?->name_en;
                $data['upazila_name'] = $mouza->upazila?->name_en;
                $data['mouza_name'] = $mouza->name_en;
            }
        }

        return $data;
    }
}
