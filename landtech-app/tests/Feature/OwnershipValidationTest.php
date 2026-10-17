<?php

namespace Tests\Feature;

use App\Http\Requests\StorePlotOwnershipRequest;
use App\Http\Requests\StorePartyRequest;
use App\Models\Party;
use App\Models\Plot;
use App\Models\PlotOwnership;
use App\Models\Role;
use App\Models\User;
use App\Rules\IdentityNumber;
use App\Rules\ShareFraction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Phase 3 — validation of ownership shares, party payloads and identity numbers.
 *
 * Shares are exact fractions, so 1/3 must stay 1/3 and must never be validated
 * as a rounded float.
 */
class OwnershipValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function validateShare(int $numerator, int $denominator): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make(
            ['share_denominator' => $denominator],
            ['share_denominator' => [new ShareFraction($numerator, $denominator)]]
        );
    }

    public function test_a_valid_fraction_passes(): void
    {
        $this->assertFalse($this->validateShare(1, 3)->fails());
        $this->assertFalse($this->validateShare(1, 1)->fails());
        $this->assertFalse($this->validateShare(2, 3)->fails());
    }

    public function test_zero_denominator_is_rejected(): void
    {
        $this->assertTrue($this->validateShare(1, 0)->fails());
    }

    public function test_negative_or_zero_numerator_is_rejected(): void
    {
        $this->assertTrue($this->validateShare(0, 3)->fails());
        $this->assertTrue($this->validateShare(-1, 3)->fails());
    }

    public function test_share_greater_than_the_whole_plot_is_rejected(): void
    {
        $this->assertTrue($this->validateShare(4, 3)->fails());
    }

    public function test_fractional_shares_sum_exactly_without_float_error(): void
    {
        $plot = Plot::factory()->create();
        $parties = Party::factory()->count(3)->create();

        foreach ($parties as $party) {
            PlotOwnership::factory()->fraction(1, 3)->create([
                'plot_id' => $plot->id,
                'party_id' => $party->id,
            ]);
        }

        $rows = $plot->currentOwnerships()->get();

        // Exact integer arithmetic — 3 * (1/3) is precisely 3/3, not 0.9999...
        $numerators = $rows->pluck('share_numerator')->sum();
        $this->assertSame(3, $numerators);
        $this->assertSame(['1/3', '1/3', '1/3'], $rows->map->shareLabel()->all());

        // Float display value is available but must not be the source of truth.
        $this->assertEqualsWithDelta(0.3333, $rows->first()->shareRatio(), 0.0001);
    }

    public function test_zero_denominator_never_causes_a_division_error(): void
    {
        $ownership = new PlotOwnership(['share_numerator' => 1, 'share_denominator' => 0]);

        // Defensive: shareRatio() must not throw even on corrupt data.
        $this->assertSame(0.0, $ownership->shareRatio());
    }

    public function test_ownership_request_rejects_a_share_over_one(): void
    {
        $user = $this->userWithRole(Role::REGISTRY_OFFICER);
        $plot = Plot::factory()->create();
        $party = Party::factory()->create();

        $request = StorePlotOwnershipRequest::create('/ownerships', 'POST', [
            'plot_id' => $plot->id,
            'party_id' => $party->id,
            'ownership_role' => PlotOwnership::ROLE_JOINT,
            'share_numerator' => 5,
            'share_denominator' => 3,
            'status' => PlotOwnership::STATUS_CURRENT,
        ]);
        $request->setUserResolver(fn() => $user);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('share_denominator', $validator->errors()->toArray());
    }

    public function test_ownership_request_rejects_disposal_before_acquisition(): void
    {
        $plot = Plot::factory()->create();
        $party = Party::factory()->create();

        $validator = Validator::make([
            'plot_id' => $plot->id,
            'party_id' => $party->id,
            'ownership_role' => PlotOwnership::ROLE_JOINT,
            'share_numerator' => 1,
            'share_denominator' => 2,
            'acquired_on' => '2020-01-01',
            'disposed_on' => '2019-01-01',
            'status' => PlotOwnership::STATUS_HISTORICAL,
        ], (new StorePlotOwnershipRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('disposed_on', $validator->errors()->toArray());
    }

    public function test_ownership_request_rejects_a_sole_share_that_is_not_whole(): void
    {
        $request = new StorePlotOwnershipRequest();

        $validator = Validator::make([
            'plot_id' => 1,
            'party_id' => 1,
            'ownership_role' => PlotOwnership::ROLE_SOLE,
            'share_numerator' => 1,
            'share_denominator' => 2,
            'status' => PlotOwnership::STATUS_CURRENT,
        ], $request->rules());

        $request->withValidator($validator);

        // passes() triggers the after() callbacks.
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('share_numerator', $validator->errors()->toArray());
    }

    public function test_ownership_request_rejects_a_disposed_record_marked_current(): void
    {
        $request = new StorePlotOwnershipRequest();

        $validator = Validator::make([
            'plot_id' => 1,
            'party_id' => 1,
            'ownership_role' => PlotOwnership::ROLE_JOINT,
            'share_numerator' => 1,
            'share_denominator' => 2,
            'acquired_on' => '2020-01-01',
            'disposed_on' => '2021-01-01',
            'status' => PlotOwnership::STATUS_CURRENT,
        ], $request->rules());

        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_identity_number_rule_accepts_bengali_digits_and_rejects_real_format(): void
    {
        $rule = new IdentityNumber();

        $bengali = Validator::make(['nid' => 'SIM-১০০২'], ['nid' => [$rule]]);
        $this->assertFalse($bengali->fails(), 'Bengali-digit synthetic NID should be accepted');

        $real = Validator::make(['nid' => '1234567890'], ['nid' => [$rule]]);
        $this->assertTrue($real->fails(), 'non-synthetic NID must be rejected in this phase');
    }

    public function test_party_request_requires_bilingual_names(): void
    {
        $validator = Validator::make([
            'party_type' => Party::TYPE_INDIVIDUAL,
            'display_name_en' => 'Only English',
        ], (new StorePartyRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('display_name_bn', $validator->errors()->toArray());
    }

    public function test_party_request_rejects_organization_with_a_date_of_birth(): void
    {
        $request = new StorePartyRequest();

        $validator = Validator::make([
            'party_type' => Party::TYPE_ORGANIZATION,
            'display_name_en' => 'Org',
            'display_name_bn' => 'সংস্থা',
            'org_registration_no' => 'SIM-ORG-9999',
            'date_of_birth' => '1990-01-01',
        ], $request->rules());

        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('date_of_birth', $validator->errors()->toArray());
    }

    public function test_party_request_requires_registration_number_for_organizations(): void
    {
        $request = new StorePartyRequest();

        $validator = Validator::make([
            'party_type' => Party::TYPE_ORGANIZATION,
            'display_name_en' => 'Org',
            'display_name_bn' => 'সংস্থা',
        ], $request->rules());

        $request->withValidator($validator);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('org_registration_no', $validator->errors()->toArray());
    }

    protected function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->named($roleName)->create();
        $user->roles()->attach($role->id);

        return $user->fresh();
    }
}
