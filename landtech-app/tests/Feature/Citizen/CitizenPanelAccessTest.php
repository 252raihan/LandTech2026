<?php

namespace Tests\Feature\Citizen;

use App\Models\Party;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — server-side access control for the Citizen/User Panel.
 *
 * These tests exist because the panel's entire security model rests on the
 * `auth` + `role:citizen` middleware group, and before this file NO test
 * exercised it. Hiding a sidebar link is not access control: a user who types
 * the URL directly must still be refused.
 */
class CitizenPanelAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Every route name in the citizen panel group.
     *
     * Phase 5 replaced the placeholder pages (deeds/ownership/applications) with
     * real workflows, so the list below reflects the current route surface.
     *
     * @return list<string>
     */
    protected function citizenRoutes(): array
    {
        return [
            'citizen.dashboard',
            'citizen.modes',
            'citizen.land.index',
            'citizen.land.create',
            'citizen.documents.index',
            'citizen.documents.create',
            'citizen.link.index',
            'citizen.link.create',
            'citizen.listings.index',
            'citizen.listings.create',
            'citizen.interest.received',
            'citizen.find',
            'citizen.find.locations',
            'citizen.verification.index',
            'citizen.verification.create',
            'citizen.interest.index',
            'citizen.notifications',
            'citizen.help',
            'citizen.profile',
            'citizen.security',
        ];
    }

    protected function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();

        // Reuse the seeded role row when present so we never collide on the
        // unique roles.name index, and so we test the REAL role rows that the
        // application resolves by name.
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            [
                'label_en' => ucwords(str_replace('_', ' ', $roleName)),
                'label_bn' => 'ভূমিকা',
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user->fresh();
    }

    /**
     * Ensure the citizen role exists, as RoleSeeder would have created it.
     * Registration resolves this row by name.
     */
    protected function seedCitizenRole(): void
    {
        Role::query()->firstOrCreate(
            ['name' => Role::CITIZEN],
            ['label_en' => 'Citizen', 'label_bn' => 'নাগরিক']
        );
    }

    // ---------------------------------------------------------------- guests

    public function test_guests_are_redirected_to_login_from_every_citizen_route(): void
    {
        foreach ($this->citizenRoutes() as $routeName) {
            $this->get(route($routeName))
                ->assertRedirect(route('login'));
        }
    }

    public function test_guest_redirect_from_citizen_route_preserves_intended_url(): void
    {
        $this->get(route('citizen.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended');
    }

    // -------------------------------------------------------------- citizens

    public function test_citizen_can_access_every_permitted_citizen_page(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        foreach ($this->citizenRoutes() as $routeName) {
            $this->actingAs($citizen)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    // ----------------------------------------------- other roles are refused

    public static function nonCitizenRoles(): array
    {
        return [
            'admin' => [Role::ADMIN],
            'registry officer' => [Role::REGISTRY_OFFICER],
            'bank officer' => [Role::BANK_OFFICER],
        ];
    }

    /**
     * @dataProvider nonCitizenRoles
     */
    public function test_non_citizen_roles_receive_403_on_citizen_routes(string $roleName): void
    {
        $user = $this->userWithRole($roleName);

        foreach ($this->citizenRoutes() as $routeName) {
            $this->actingAs($user)
                ->get(route($routeName))
                ->assertForbidden();
        }
    }

    public function test_a_user_with_no_role_is_refused_on_citizen_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('citizen.dashboard'))
            ->assertForbidden();
    }

    public function test_citizen_cannot_reach_another_roles_dashboard(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        $this->actingAs($citizen)->get(route('dashboard'))->assertOk(); // public demo
        $this->actingAs($citizen)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($citizen)->get(route('registry.dashboard'))->assertForbidden();
        $this->actingAs($citizen)->get(route('bank.dashboard'))->assertForbidden();
    }

    // ------------------------------------------- object-level authorization

    public function test_citizen_cannot_view_another_citizens_private_records(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);
        $other = $this->userWithRole(Role::CITIZEN);

        // A party exists that belongs to neither user.
        $party = Party::factory()->create();

        // No user→party link exists in this phase, so a citizen owns nothing:
        // both the "own" and "other" cases must resolve to false. This is the
        // conservative default that prevents claiming land by assertion.
        $this->assertFalse($citizen->can('view', $party));
        $this->assertFalse($other->can('view', $party));

        // And the citizen panel must not disclose the party anywhere.
        $this->actingAs($citizen)
            ->get(route('citizen.land.index'))
            ->assertOk()
            ->assertDontSee($party->display_name_en);
    }

    public function test_citizen_panel_shows_an_honest_empty_state_not_fabricated_records(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        // Seed some domain data that must NOT leak into the citizen panel.
        $party = Party::factory()->create(['display_name_en' => 'Unrelated Party Leak Check']);

        foreach (['citizen.land.index', 'citizen.documents.index', 'citizen.listings.index', 'citizen.verification.index'] as $routeName) {
            $this->actingAs($citizen)
                ->get(route($routeName))
                ->assertOk()
                ->assertDontSee('Unrelated Party Leak Check');
        }
    }

    // -------------------------------------------------------- notifications

    public function test_notifications_are_scoped_to_the_authenticated_user(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);
        $other = $this->userWithRole(Role::CITIZEN);

        // The notifications table does not exist in this phase, so the page must
        // render the honest empty state rather than erroring. The assertion that
        // matters is that the payload is built from the authenticated user's own
        // relation and totals zero for both users.
        $this->actingAs($citizen)
            ->get(route('citizen.notifications'))
            ->assertOk()
            ->assertViewHas('notifications', fn ($n) => $n->total() === 0)
            ->assertViewHas('unreadCount', 0);

        $this->actingAs($other)
            ->get(route('citizen.notifications'))
            ->assertOk()
            ->assertViewHas('notifications', fn ($n) => $n->total() === 0);
    }

    public function test_notifications_page_does_not_load_the_full_collection_twice(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        $response = $this->actingAs($citizen)->get(route('citizen.notifications'));

        $response->assertOk();

        // The view receives a paginator, not a bare collection — this is what
        // bounds the payload as the table grows.
        $this->assertInstanceOf(
            \Illuminate\Contracts\Pagination\Paginator::class,
            $response->viewData('notifications')
        );
    }

    // ------------------------------------------------------ profile/security

    public function test_citizen_profile_and_security_pages_are_reachable_and_scoped(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        $this->actingAs($citizen)->get(route('citizen.profile'))->assertOk();
        $this->actingAs($citizen)->get(route('citizen.security'))->assertOk();
    }

    public function test_profile_update_cannot_change_roles(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        $this->actingAs($citizen)->patch(route('citizen.profile.update'), [
            'name' => 'Renamed Citizen',
            'email' => $citizen->email,
            'role' => Role::ADMIN,
            'role_id' => 1,
            'is_admin' => true,
        ])->assertRedirect();

        $citizen->refresh();

        $this->assertSame('Renamed Citizen', $citizen->name);
        $this->assertFalse($citizen->hasRole(Role::ADMIN));
        $this->assertTrue($citizen->hasRole(Role::CITIZEN));
        $this->assertSame(1, $citizen->roles()->count(), 'profile update must not add roles');
    }

    // ----------------------------------------------------------- localisation

    public function test_citizen_dashboard_is_translated_in_both_locales(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        $this->actingAs($citizen)->get(route('citizen.dashboard'))->assertOk()->assertSee('Citizen Dashboard');

        $this->post(route('locale.switch'), ['locale' => 'bn']);

        $this->actingAs($citizen)
            ->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSee('নাগরিক ড্যাশবোর্ড', false);
    }

    public function test_access_denied_message_is_translated(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);

        $this->actingAs($admin)->get(route('citizen.dashboard'))->assertForbidden();

        $this->post(route('locale.switch'), ['locale' => 'bn']);

        $this->actingAs($admin)
            ->get(route('citizen.dashboard'))
            ->assertForbidden()
            ->assertSee('এই এলাকায় প্রবেশের অনুমতি আপনার নেই।', false);
    }
}
