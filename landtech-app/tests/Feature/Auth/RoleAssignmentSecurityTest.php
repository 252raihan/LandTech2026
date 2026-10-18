<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — role-assignment and post-login redirect security.
 *
 * Covers the two ways privilege could be obtained without an administrator:
 *   1. submitting role-escalation fields to public registration or profile
 *      update (mass assignment / manipulated request);
 *   2. planting an intended URL to bypass the post-login role redirect.
 */
class RoleAssignmentSecurityTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();

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

    protected function seedCitizenRole(): void
    {
        Role::query()->firstOrCreate(
            ['name' => Role::CITIZEN],
            ['label_en' => 'Citizen', 'label_bn' => 'নাগরিক']
        );
    }

    /**
     * The privileged roles that must never be obtainable via public input.
     *
     * `super_admin` is deliberately NOT listed — it does not exist yet. The
     * registration test below asserts that even a bogus super_admin value is
     * ignored, but no assertion is made that the role is assignable.
     *
     * @return array<string, array{string}>
     */
    public static function privilegedRoles(): array
    {
        return [
            'admin' => [Role::ADMIN],
            'registry officer' => [Role::REGISTRY_OFFICER],
            'bank officer' => [Role::BANK_OFFICER],
        ];
    }

    // -------------------------------------------------------- registration

    public function test_a_newly_registered_user_receives_only_the_citizen_role(): void
    {
        $this->seedCitizenRole();

        $this->post('/register', [
            'name' => 'New Person',
            'email' => 'new.person@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect();

        $user = User::where('email', 'new.person@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole(Role::CITIZEN), 'registration must grant citizen');
        $this->assertSame(1, $user->roles()->count(), 'registration must grant exactly one role');
    }

    /**
     * @dataProvider privilegedRoles
     */
    public function test_registration_cannot_self_assign_a_privileged_role(string $roleName): void
    {
        $this->seedCitizenRole();

        // The privileged role exists in the DB — proving the guard is the
        // explicit field allow-list, not merely the role's absence.
        Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label_en' => ucwords(str_replace('_', ' ', $roleName)), 'label_bn' => 'ভূমিকা']
        );

        $this->post('/register', [
            'name' => 'Escalation Attempt',
            'email' => "escalate.{$roleName}@example.test",
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => $roleName,
            'role_id' => 1,
            'is_admin' => true,
            'roles' => [$roleName],
        ]);

        $user = User::where('email', "escalate.{$roleName}@example.test")->firstOrFail();

        $this->assertFalse($user->hasRole($roleName), "public registration must not grant {$roleName}");
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->hasRole(Role::CITIZEN));
        $this->assertSame(1, $user->roles()->count());
    }

    public function test_registration_cannot_assign_a_nonexistent_super_admin_role(): void
    {
        $this->seedCitizenRole();

        $this->post('/register', [
            'name' => 'Super Attempt',
            'email' => 'super.attempt@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'super_admin',
        ]);

        $user = User::where('email', 'super.attempt@example.test')->firstOrFail();

        // The role does not exist, so it cannot be held — but assert the intent
        // explicitly so this test fails loudly if super_admin is ever added
        // without a corresponding guard here.
        $this->assertFalse($user->hasRole('super_admin'));
        $this->assertTrue($user->hasRole(Role::CITIZEN));
    }

    public function test_registration_ignores_role_fields_even_when_the_role_exists(): void
    {
        $this->seedCitizenRole();
        Role::query()->firstOrCreate(
            ['name' => Role::ADMIN],
            ['label_en' => 'Administrator', 'label_bn' => 'প্রশাসক']
        );

        $this->post('/register', [
            'name' => 'Mass Assignment Attempt',
            'email' => 'mass.assignment@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => Role::ADMIN,
        ]);

        $user = User::where('email', 'mass.assignment@example.test')->firstOrFail();

        $this->assertFalse($user->hasRole(Role::ADMIN));
        $this->assertSame(1, $user->roles()->count());
    }

    // -------------------------------------------------------- profile update

    public function test_profile_update_cannot_assign_a_role(): void
    {
        $user = $this->userWithRole(Role::CITIZEN);

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Still A Citizen',
            'email' => $user->email,
            'role' => Role::ADMIN,
            'role_id' => 1,
            'is_admin' => true,
        ]);

        $user->refresh();

        $this->assertFalse($user->hasRole(Role::ADMIN));
        $this->assertSame(1, $user->roles()->count());
    }

    public function test_user_model_mass_assignment_cannot_set_privilege_columns(): void
    {
        $user = User::factory()->create();

        $user->fill([
            'name' => 'Direct Fill',
            'is_admin' => true,
            'role' => Role::ADMIN,
            'role_id' => 1,
        ]);

        $user->save();
        $user->refresh();

        // `is_admin` / `role` / `role_id` are not in $fillable and are not real
        // columns, so nothing is persisted and no role is created.
        $this->assertFalse($user->hasRole(Role::ADMIN));
        $this->assertSame(0, $user->roles()->count());
    }

    // ------------------------------------------------- login redirect policy

    /**
     * @return array<string, array{string, string}>
     */
    public static function roleLandingPages(): array
    {
        return [
            'citizen' => [Role::CITIZEN, 'citizen.dashboard'],
            'registry officer' => [Role::REGISTRY_OFFICER, 'registry.dashboard'],
            'bank officer' => [Role::BANK_OFFICER, 'bank.dashboard'],
            'admin' => [Role::ADMIN, 'admin.dashboard'],
        ];
    }

    /**
     * @dataProvider roleLandingPages
     */
    public function test_each_role_lands_on_its_own_dashboard_after_login(string $roleName, string $expectedRoute): void
    {
        $user = $this->userWithRole($roleName);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route($expectedRoute, absolute: false));
    }

    public function test_a_user_with_no_role_lands_on_the_pending_page_not_the_public_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('account.pending', absolute: false));

        // And explicitly NOT the public demo dashboard.
        $this->assertNotSame(
            route('dashboard', absolute: false),
            $response->headers->get('Location')
        );
    }

    /**
     * The core H2 regression test: an intended URL pointing at ANOTHER role's
     * dashboard must not override the user's own authorized landing page.
     */
    public function test_intended_url_for_another_roles_dashboard_cannot_override_authorization(): void
    {
        $admin = $this->userWithRole(Role::ADMIN);

        // Plant the citizen dashboard as the intended URL.
        $this->get(route('citizen.dashboard'))->assertRedirect(route('login'));

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));

        // Following it must NOT land on a 403.
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    /**
     * @dataProvider roleLandingPages
     */
    public function test_intended_url_for_a_foreign_dashboard_always_yields_the_own_dashboard(
        string $roleName,
        string $expectedRoute
    ): void {
        $foreignRoutes = array_diff(
            array_column(self::roleLandingPages(), 1),
            [$expectedRoute]
        );

        foreach ($foreignRoutes as $foreignRoute) {
            // Plant the foreign dashboard as the intended URL directly in the
            // session. Requesting it would 403 for this user before the guest
            // middleware could store it, which is itself the correct behaviour.
            $this->flushSession();

            $user = $this->userWithRole($roleName);

            $this->withSession(['url.intended' => route($foreignRoute, absolute: false)])
                ->post('/login', [
                    'email' => $user->email,
                    'password' => 'password',
                ])
                ->assertRedirect(route($expectedRoute, absolute: false));

            $this->flushSession();
        }
    }

    public function test_a_safe_non_dashboard_intended_url_is_still_honoured(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        // /profile is auth-only, not role-restricted, so it is a legitimate
        // intended destination and must not be discarded.
        $this->get('/profile')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $citizen->email,
            'password' => 'password',
        ])->assertRedirect('/profile');
    }

    public function test_an_off_host_intended_url_is_rejected(): void
    {
        $this->seedCitizenRole();
        $citizen = $this->userWithRole(Role::CITIZEN);

        $response = $this->withSession(['url.intended' => 'https://evil.example.com/steal'])
            ->post('/login', [
                'email' => $citizen->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();

        // The role landing page wins; we never emit the foreign host.
        $response->assertRedirect(route('citizen.dashboard', absolute: false));
        $this->assertStringNotContainsString('evil.example.com', $response->headers->get('Location'));
    }

    public function test_login_does_not_redirect_loop_for_a_role_less_user(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('account.pending', absolute: false));

        // The pending page must render (auth-only), not bounce back to login.
        $this->actingAs($user)->get(route('account.pending'))->assertOk();
    }

    // ------------------------------------------------------- localisation

    public function test_authorization_error_and_pending_page_are_bilingual(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.pending'))->assertOk()->assertSee('no assigned role');

        $this->post(route('locale.switch'), ['locale' => 'bn']);

        $this->actingAs($user)
            ->get(route('account.pending'))
            ->assertOk()
            ->assertSee('কোনো ভূমিকা নির্ধারিত নেই', false);
    }

    // ------------------------------------------------------------- baseline

    public function test_existing_breeze_login_still_rejects_bad_credentials(): void
    {
        $user = $this->userWithRole(Role::CITIZEN);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_public_demo_pages_remain_accessible(): void
    {
        foreach (['dashboard', 'saga', 'crypto-verify', 'audit-ledger'] as $slug) {
            $this->get("/{$slug}")->assertOk();
        }
    }
}
