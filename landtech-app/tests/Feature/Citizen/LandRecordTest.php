<?php

namespace Tests\Feature\Citizen;

use App\Models\LandRecord;
use App\Models\Party;
use App\Models\PartyUserLink;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 — landowner land records.
 *
 * The two properties that matter most here:
 *   1. A submission NEVER becomes `verified` by any owner action.
 *   2. A citizen can never read or edit another citizen's record.
 */
class LandRecordTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label_en' => ucwords(str_replace('_', ' ', $roleName)), 'label_bn' => 'ভূমিকা']
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh();
    }

    protected function citizen(): User
    {
        return $this->userWithRole(Role::CITIZEN);
    }

    /**
     * A minimal valid payload.
     *
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'khatian_no' => 'KH-1001',
            'dag_no' => 'DG-2001',
            'area_unit' => 'decimal',
            'claim_share_numerator' => 1,
            'claim_share_denominator' => 1,
            'intended_use' => LandRecord::INTENT_RECORD_KEEPING,
            'action' => 'submit',
        ], $overrides);
    }

    // ------------------------------------------------------------- access

    public function test_guests_cannot_reach_land_routes(): void
    {
        $this->get(route('citizen.land.index'))->assertRedirect(route('login'));
        $this->get(route('citizen.land.create'))->assertRedirect(route('login'));
        $this->post(route('citizen.land.store'), $this->payload())->assertRedirect(route('login'));
    }

    public function test_citizen_can_create_a_land_record(): void
    {
        $citizen = $this->citizen();

        $response = $this->actingAs($citizen)->post(route('citizen.land.store'), $this->payload());

        $response->assertRedirect();

        $record = LandRecord::query()->where('user_id', $citizen->id)->firstOrFail();

        $this->assertSame('KH-1001', $record->khatian_no);
        $this->assertSame('DG-2001', $record->dag_no);
        $this->assertSame($citizen->id, $record->user_id);
    }

    public function test_other_roles_cannot_reach_land_routes(): void
    {
        foreach ([Role::ADMIN, Role::REGISTRY_OFFICER, Role::BANK_OFFICER] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('citizen.land.index'))
                ->assertForbidden();
        }
    }

    // -------------------------------------------- status is never self-set

    public function test_a_submitted_record_is_pending_verification_not_verified(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->post(route('citizen.land.store'), $this->payload());

        $record = LandRecord::query()->where('user_id', $citizen->id)->firstOrFail();

        $this->assertSame(LandRecord::STATUS_PENDING_VERIFICATION, $record->status);
        $this->assertNotSame(LandRecord::STATUS_VERIFIED, $record->status);
        $this->assertFalse($record->isVerified());
        $this->assertNotNull($record->submitted_at);
        $this->assertNull($record->reviewed_by);
    }

    public function test_a_draft_is_saved_as_draft(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->post(
            route('citizen.land.store'),
            $this->payload(['action' => 'draft'])
        );

        $record = LandRecord::query()->where('user_id', $citizen->id)->firstOrFail();

        $this->assertSame(LandRecord::STATUS_DRAFT, $record->status);
        $this->assertNull($record->submitted_at);
    }

    public function test_a_client_cannot_post_a_verified_status(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->post(route('citizen.land.store'), $this->payload([
            'status' => LandRecord::STATUS_VERIFIED,
            'reviewed_by' => 1,
            'party_id' => 99,
            'user_id' => 12345,
        ]));

        $record = LandRecord::query()->where('user_id', $citizen->id)->firstOrFail();

        $this->assertSame(LandRecord::STATUS_PENDING_VERIFICATION, $record->status);
        $this->assertNull($record->reviewed_by);
        $this->assertNull($record->party_id, 'party_id must not be settable from the request');
        $this->assertSame($citizen->id, $record->user_id, 'user_id must not be settable from the request');
    }

    public function test_submitting_does_not_attach_a_party_without_a_verified_link(): void
    {
        $citizen = $this->citizen();
        $party = Party::factory()->create();

        // A PENDING link exists — it must confer nothing.
        PartyUserLink::create([
            'user_id' => $citizen->id,
            'party_id' => $party->id,
            'status' => PartyUserLink::STATUS_PENDING,
            'claim_basis' => PartyUserLink::BASIS_NID,
            'claim_note' => 'I believe this is my record.',
        ]);

        $this->actingAs($citizen)->post(route('citizen.land.store'), $this->payload());

        $record = LandRecord::query()->where('user_id', $citizen->id)->firstOrFail();

        $this->assertNull(
            $record->party_id,
            'a pending link must never attribute a land record to a party'
        );
    }

    public function test_a_verified_link_attributes_the_party(): void
    {
        $citizen = $this->citizen();
        $party = Party::factory()->create();

        PartyUserLink::create([
            'user_id' => $citizen->id,
            'party_id' => $party->id,
            'status' => PartyUserLink::STATUS_VERIFIED,
            'claim_basis' => PartyUserLink::BASIS_NID,
            'claim_note' => 'Reviewed and approved.',
        ]);

        $this->actingAs($citizen)->post(route('citizen.land.store'), $this->payload());

        $record = LandRecord::query()->where('user_id', $citizen->id)->firstOrFail();

        $this->assertSame($party->id, $record->party_id);
    }

    // -------------------------------------------------- share is never 100%

    public function test_a_fractional_share_is_stored_exactly(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->post(route('citizen.land.store'), $this->payload([
            'claim_share_numerator' => 1,
            'claim_share_denominator' => 3,
        ]));

        $record = LandRecord::query()->where('user_id', $citizen->id)->firstOrFail();

        $this->assertSame(1, $record->claim_share_numerator);
        $this->assertSame(3, $record->claim_share_denominator);
        $this->assertSame('1/3', $record->shareLabel());
    }

    public function test_a_share_greater_than_one_is_rejected(): void
    {
        $this->actingAs($this->citizen())
            ->post(route('citizen.land.store'), $this->payload([
                'claim_share_numerator' => 3,
                'claim_share_denominator' => 2,
            ]))
            ->assertSessionHasErrors('claim_share_denominator');

        $this->assertSame(0, LandRecord::query()->count());
    }

    // --------------------------------------------------------- validation

    public function test_khatian_and_dag_are_required(): void
    {
        $this->actingAs($this->citizen())
            ->post(route('citizen.land.store'), $this->payload([
                'khatian_no' => '',
                'dag_no' => '',
            ]))
            ->assertSessionHasErrors(['khatian_no', 'dag_no']);
    }

    public function test_an_invalid_survey_type_is_rejected(): void
    {
        $this->actingAs($this->citizen())
            ->post(route('citizen.land.store'), $this->payload(['survey_type' => 'XX']))
            ->assertSessionHasErrors('survey_type');
    }

    // ------------------------------------------------- cross-user isolation

    public function test_a_citizen_cannot_view_another_citizens_record(): void
    {
        $owner = $this->citizen();
        $other = $this->citizen();

        $record = LandRecord::factory()->create(['user_id' => $owner->id]);

        // The owner-scoped lookup 404s rather than 403s, so the existence of
        // another user's record is not even disclosed.
        $this->actingAs($other)
            ->get(route('citizen.land.show', $record->id))
            ->assertNotFound();

        $this->actingAs($other)
            ->get(route('citizen.land.edit', $record->id))
            ->assertNotFound();
    }

    public function test_a_citizen_cannot_update_another_citizens_record(): void
    {
        $owner = $this->citizen();
        $other = $this->citizen();

        $record = LandRecord::factory()->create([
            'user_id' => $owner->id,
            'khatian_no' => 'ORIGINAL',
        ]);

        $this->actingAs($other)
            ->patch(route('citizen.land.update', $record->id), $this->payload(['khatian_no' => 'HIJACKED']))
            ->assertNotFound();

        $this->assertSame('ORIGINAL', $record->fresh()->khatian_no);
    }

    public function test_a_citizen_cannot_delete_another_citizens_record(): void
    {
        $owner = $this->citizen();
        $other = $this->citizen();

        $record = LandRecord::factory()->create([
            'user_id' => $owner->id,
            'status' => LandRecord::STATUS_DRAFT,
        ]);

        $this->actingAs($other)
            ->delete(route('citizen.land.destroy', $record->id))
            ->assertNotFound();

        $this->assertNull($record->fresh()->deleted_at);
    }

    public function test_a_citizen_cannot_submit_another_citizens_draft(): void
    {
        $owner = $this->citizen();
        $other = $this->citizen();

        $record = LandRecord::factory()->create([
            'user_id' => $owner->id,
            'status' => LandRecord::STATUS_DRAFT,
        ]);

        $this->actingAs($other)
            ->post(route('citizen.land.submit', $record->id))
            ->assertNotFound();

        $this->assertSame(LandRecord::STATUS_DRAFT, $record->fresh()->status);
    }

    // ------------------------------------------------ edit lock after review

    public function test_a_record_under_review_cannot_be_edited_by_its_owner(): void
    {
        $citizen = $this->citizen();

        $record = LandRecord::factory()->create([
            'user_id' => $citizen->id,
            'status' => LandRecord::STATUS_PENDING_VERIFICATION,
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.land.edit', $record->id))
            ->assertForbidden();

        $this->actingAs($citizen)
            ->patch(route('citizen.land.update', $record->id), $this->payload(['khatian_no' => 'CHANGED']))
            ->assertForbidden();

        $this->assertNotSame('CHANGED', $record->fresh()->khatian_no);
    }

    public function test_a_draft_is_editable_by_its_owner(): void
    {
        $citizen = $this->citizen();

        $record = LandRecord::factory()->create([
            'user_id' => $citizen->id,
            'status' => LandRecord::STATUS_DRAFT,
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.land.edit', $record->id))
            ->assertOk();

        $this->actingAs($citizen)
            ->patch(route('citizen.land.update', $record->id), $this->payload([
                'khatian_no' => 'UPDATED',
                'action' => 'draft',
            ]))
            ->assertRedirect();

        $this->assertSame('UPDATED', $record->fresh()->khatian_no);
    }

    public function test_a_citizen_cannot_review_their_own_record(): void
    {
        $citizen = $this->citizen();

        $record = LandRecord::factory()->create([
            'user_id' => $citizen->id,
            'status' => LandRecord::STATUS_PENDING_VERIFICATION,
        ]);

        // The review ability is registry/admin only, and never on one's own record.
        $this->assertFalse($citizen->can('review', $record));
    }

    public function test_an_owner_can_request_a_correction(): void
    {
        $citizen = $this->citizen();

        $record = LandRecord::factory()->create([
            'user_id' => $citizen->id,
            'status' => LandRecord::STATUS_PENDING_VERIFICATION,
        ]);

        $this->actingAs($citizen)
            ->post(route('citizen.land.correction', $record->id), [
                'correction_note' => 'The dag number is incorrect.',
            ])
            ->assertRedirect();

        $this->assertSame(LandRecord::STATUS_CORRECTION_REQUESTED, $record->fresh()->status);
    }

    // ------------------------------------------------------- localisation

    public function test_land_pages_are_translated_in_both_locales(): void
    {
        $citizen = $this->citizen();

        $this->actingAs($citizen)->get(route('citizen.land.index'))
            ->assertOk()
            ->assertSee('My Land');

        $this->post(route('locale.switch'), ['locale' => 'bn']);

        $this->actingAs($citizen)->get(route('citizen.land.index'))
            ->assertOk()
            ->assertSee('আমার জমি', false);
    }

    public function test_status_labels_are_translated_not_raw_values(): void
    {
        $citizen = $this->citizen();

        LandRecord::factory()->create([
            'user_id' => $citizen->id,
            'status' => LandRecord::STATUS_PENDING_VERIFICATION,
        ]);

        $response = $this->actingAs($citizen)->get(route('citizen.land.index'));

        $response->assertOk()
            ->assertSee('Pending verification')
            ->assertDontSee('pending_verification');
    }
}
