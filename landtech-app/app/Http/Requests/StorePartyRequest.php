<?php

namespace App\Http\Requests;

use App\Models\Party;
use App\Rules\IdentityNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Phase 3 — create/update a Party, optionally with its primary identity.
 *
 * Bilingual names are required in BOTH scripts, matching the Phase 1 geo
 * convention and the Bengali/English UI requirement.
 */
class StorePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Party::class) ?? false;
    }

    public function rules(): array
    {
        $partyId = $this->route('party')?->id;

        return [
            'party_type' => ['required', Rule::in([Party::TYPE_INDIVIDUAL, Party::TYPE_ORGANIZATION])],

            'display_name_en' => ['required', 'string', 'max:255'],
            'display_name_bn' => ['required', 'string', 'max:255'],

            'legal_name_en' => ['nullable', 'string', 'max:255'],
            'legal_name_bn' => ['nullable', 'string', 'max:255'],

            // Organization-only. Required-ness is enforced in withValidator()
            // (see below) so it works under standalone Validator instances too.
            'org_registration_no' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('parties', 'org_registration_no')->ignore($partyId),
            ],

            // Individual-only.
            'date_of_birth' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],

            'status' => ['nullable', Rule::in([
                Party::STATUS_ACTIVE,
                Party::STATUS_MERGED,
                Party::STATUS_ARCHIVED,
            ])],

            // Optional primary identity submitted alongside the party.
            'identity.id_type' => ['nullable', Rule::in([
                \App\Models\PartyIdentity::TYPE_NID,
                \App\Models\PartyIdentity::TYPE_BIRTH_REGISTRATION,
                \App\Models\PartyIdentity::TYPE_PASSPORT,
                \App\Models\PartyIdentity::TYPE_ORG_REG,
            ])],
            'identity.id_number' => ['nullable', new IdentityNumber()],
        ];
    }

    /**
     * Cross-field consistency: an organization must not carry a date of birth,
     * and an individual must not carry a registration number.
     *
     * Reads from the VALIDATOR's data (not $this->input()) so the checks behave
     * identically whether the request is validated by the framework or by a
     * standalone Validator instance in tests.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $type = $data['party_type'] ?? null;

            $hasDateOfBirth = ! empty($data['date_of_birth']);
            $hasOrgReg = ! empty($data['org_registration_no']);

            // Type-conditional requirement.
            if ($type === Party::TYPE_ORGANIZATION && ! $hasOrgReg) {
                $validator->errors()->add('org_registration_no', 'An organization registration number is required.');
            }

            if ($type === Party::TYPE_INDIVIDUAL && ! $hasDateOfBirth) {
                $validator->errors()->add('date_of_birth', 'A date of birth is required for an individual.');
            }

            if ($type === Party::TYPE_ORGANIZATION && $hasDateOfBirth) {
                $validator->errors()->add('date_of_birth', 'An organization must not have a date of birth.');
            }

            if ($type === Party::TYPE_INDIVIDUAL && $hasOrgReg) {
                $validator->errors()->add('org_registration_no', 'An individual must not have an organization registration number.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'display_name_en' => 'English name',
            'display_name_bn' => 'Bengali name',
            'legal_name_en' => 'English legal name',
            'legal_name_bn' => 'Bengali legal name',
            'org_registration_no' => 'organization registration number',
            'date_of_birth' => 'date of birth',
            'identity.id_number' => 'identity number',
        ];
    }
}
