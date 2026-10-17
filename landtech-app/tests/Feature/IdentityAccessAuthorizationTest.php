<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\PartyIdentity;
use App\Models\Role;
use App\Models\SensitiveReadAudit;
use App\Models\User;
use App\Services\IdentityAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 — server-side authorization for identity access, and the requirement
 * that every sensitive read is audited with actor, purpose, subject and action.
 */
class IdentityAccessAuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('privacy.nid_hmac_key', base64_encode(str_repeat('k', 32)));
        config()->set('privacy.nid_hmac_key_version', 1);
        config()->set('privacy.accepted_key_versions', [1]);
        config()->set('privacy.fail_closed', true);
    }

    protected function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::factory()->named($roleName)->create();
        $user->roles()->attach($role->id);

        return $user->fresh();
    }

    protected function identity(string $number = 'SIM-AUTH-01'): PartyIdentity
    {
        $party = Party::factory()->create();
        $identity = new PartyIdentity(['party_id' => $party->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $identity->setIdentityNumber($number);
        $identity->save();

        return $identity->fresh();
    }

    public function test_masked_read_is_allowed_for_bank_officer_and_audited(): void
    {
        $officer = $this->userWithRole(Role::BANK_OFFICER);
        $identity = $this->identity();

        $masked = app(IdentityAccessService::class)->viewMasked($officer, $identity);

        $this->assertSame('•••• TH01', $masked);
        $this->assertStringNotContainsString('SIM', $masked);

        $audit = SensitiveReadAudit::query()->latest('id')->first();
        $this->assertSame(SensitiveReadAudit::ACTION_VIEW_MASKED, $audit->action);
        $this->assertSame($officer->id, $audit->user_id);
        $this->assertSame($identity->id, $audit->subject_identity_id);
        $this->assertSame($identity->party_id, $audit->subject_party_id);
    }

    public function test_bank_officer_cannot_read_full_nid(): void
    {
        $officer = $this->userWithRole(Role::BANK_OFFICER);
        $identity = $this->identity();

        $this->expectException(AuthorizationException::class);

        app(IdentityAccessService::class)->viewFull($officer, $identity, 'lien due diligence');
    }

    public function test_registry_officer_can_read_full_nid_with_a_purpose_and_it_is_audited(): void
    {
        $officer = $this->userWithRole(Role::REGISTRY_OFFICER);
        $identity = $this->identity();

        $value = app(IdentityAccessService::class)->viewFull($officer, $identity, 'deed registration');

        $this->assertSame('SIMAUTH01', $value);

        $audit = SensitiveReadAudit::query()->latest('id')->first();
        $this->assertSame(SensitiveReadAudit::ACTION_VIEW_FULL, $audit->action);
        $this->assertSame('deed registration', $audit->purpose);
        $this->assertSame($officer->id, $audit->user_id);
    }

    public function test_full_read_without_a_purpose_is_rejected(): void
    {
        $officer = $this->userWithRole(Role::REGISTRY_OFFICER);
        $identity = $this->identity();

        $this->expectException(AuthorizationException::class);

        app(IdentityAccessService::class)->viewFull($officer, $identity, '   ');
    }

    public function test_citizen_cannot_read_full_nid_or_search(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);
        $identity = $this->identity();

        try {
            app(IdentityAccessService::class)->viewFull($citizen, $identity, 'curiosity');
            $this->fail('citizen should not be able to read a full NID');
        } catch (AuthorizationException) {
            // expected
        }

        $this->expectException(AuthorizationException::class);
        app(IdentityAccessService::class)->searchByNid($citizen, 'SIM-AUTH-01', 'browsing');
    }

    public function test_search_by_nid_returns_matches_and_audits_the_attempt(): void
    {
        $officer = $this->userWithRole(Role::REGISTRY_OFFICER);
        $identity = $this->identity('SIM-SEARCH-77');

        $matches = app(IdentityAccessService::class)->searchByNid($officer, 'SIM-SEARCH-77', 'ownership check');

        $this->assertCount(1, $matches);
        $this->assertSame($identity->id, $matches->first()->id);

        $audit = SensitiveReadAudit::query()->latest('id')->first();
        $this->assertSame(SensitiveReadAudit::ACTION_SEARCH_NID, $audit->action);
        $this->assertSame('ownership check', $audit->purpose);
    }

    public function test_search_finds_a_record_written_with_bengali_digits_using_ascii_query(): void
    {
        $officer = $this->userWithRole(Role::REGISTRY_OFFICER);

        $party = Party::factory()->create();
        $identity = new PartyIdentity(['party_id' => $party->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $identity->setIdentityNumber('SIM-৪২৪২');
        $identity->save();

        $matches = app(IdentityAccessService::class)->searchByNid($officer, 'SIM-4242', 'cross-script check');

        $this->assertCount(1, $matches);
        $this->assertSame($identity->id, $matches->first()->id);
    }

    public function test_export_requires_admin(): void
    {
        $officer = $this->userWithRole(Role::REGISTRY_OFFICER);

        $this->expectException(AuthorizationException::class);

        app(IdentityAccessService::class)->export($officer, 'bulk export');
    }

    public function test_admin_export_is_audited(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);
        $this->identity('SIM-EXPORT-01');

        app(IdentityAccessService::class)->export($admin, 'regulatory reporting');

        $audit = SensitiveReadAudit::query()->latest('id')->first();
        $this->assertSame(SensitiveReadAudit::ACTION_EXPORT, $audit->action);
        $this->assertSame($admin->id, $audit->user_id);
    }

    public function test_party_policy_blocks_unauthorized_roles(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);
        $party = Party::factory()->create();

        $this->assertFalse($citizen->can('viewAny', Party::class));
        $this->assertFalse($citizen->can('update', $party));

        $admin = $this->userWithRole(Role::ADMIN);
        $this->assertTrue($admin->can('update', $party));
        $this->assertTrue($admin->can('delete', $party));
    }
}
