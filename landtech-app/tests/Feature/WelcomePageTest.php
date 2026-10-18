<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Demo\DemoMode;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — the public Welcome / Login landing page and Demo Mode.
 *
 * The landing page is public, so these tests are as much about what it must NOT
 * do (leak records, credentials or role data) as about what it must do.
 */
class WelcomePageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Demo Mode is off unless a test turns it on explicitly.
        config()->set('landtech.demo_mode', false);
    }

    protected function enableDemoMode(): void
    {
        config()->set('landtech.demo_mode', true);
    }

    // ------------------------------------------------------------- root page

    public function test_guest_sees_the_welcome_page_at_the_root_url(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('welcome');
        $response->assertSee(__('welcome.hero.heading'));
    }

    public function test_root_does_not_redirect_guests_to_the_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertNotSame(
            route('dashboard', absolute: false),
            $response->headers->get('Location'),
            'the root URL must not redirect guests to the dashboard'
        );
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_welcome_route_is_named_and_the_dashboard_route_still_exists(): void
    {
        $this->assertSame(url('/'), route('welcome'));

        // The public M1–M10 demo dashboard must remain reachable and unchanged.
        $this->get('/dashboard')->assertOk();
    }

    // ---------------------------------------------------------- login linking

    public function test_login_links_point_at_the_existing_login_route(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('login'), false);
        $response->assertSee(route('register'), false);
    }

    public function test_authenticated_user_can_still_visit_the_root(): void
    {
        $citizen = $this->userWithRole(Role::CITIZEN);

        $response = $this->actingAs($citizen)->get('/');

        $response->assertOk();
        $response->assertViewIs('welcome');
    }

    // ------------------------------------------------------- public modules

    public function test_every_public_demo_module_remains_accessible(): void
    {
        $slugs = [
            'dashboard',
            'saga',
            'sync-webhook',
            'crypto-verify',
            'plot-lock',
            'ekyc-biometric',
            'bank-lien',
            'ai-ocr-conflict',
            'faraz-calculator',
            'gis-map',
            'risk-scoring',
            'audit-ledger',
        ];

        foreach ($slugs as $slug) {
            $this->get("/{$slug}")->assertOk();
        }
    }

    public function test_welcome_page_links_to_the_public_modules(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('audit-ledger'), false);
        $response->assertSee(route('gis-map'), false);
        $response->assertSee(route('dashboard'), false);
    }

    // ---------------------------------------------- no privileged disclosure

    public function test_welcome_page_exposes_no_credentials_or_privileged_records(): void
    {
        // Seed the full demo dataset, including a party with an encrypted NID.
        $this->seed();

        $response = $this->get('/');

        $response->assertOk();

        // No identity material of any kind.
        $response->assertDontSee('SIM-100001', false);
        $response->assertDontSee('id_number_encrypted', false);
        $response->assertDontSee('id_number_lookup_hmac', false);

        // No password / key material.
        $response->assertDontSee('password', false);
        $response->assertDontSee('APP_KEY', false);
        $response->assertDontSee('NID_HMAC_KEY', false);
        $response->assertDontSee('remember_token', false);

        // No real seeded party names leak into the public page.
        $response->assertDontSee('Simulated Individual One', false);
    }

    public function test_welcome_page_never_prints_demo_account_credentials(): void
    {
        $this->enableDemoMode();
        $this->seed();

        $response = $this->get('/');

        $response->assertOk();

        // Demo account emails are configuration, not page content, and no
        // password value is ever rendered.
        foreach (DatabaseSeeder::DEMO_ACCOUNTS as $email) {
            $response->assertDontSee($email, false);
        }
    }

    // ------------------------------------------------------------- demo mode

    public function test_demo_mode_is_disabled_by_default(): void
    {
        $this->assertFalse(
            DemoMode::enabled(),
            'Demo Mode must be off unless explicitly enabled'
        );
    }

    public function test_demo_mode_requires_the_config_flag(): void
    {
        config()->set('landtech.demo_mode', false);
        $this->assertFalse(DemoMode::enabled());

        config()->set('landtech.demo_mode', true);
        $this->assertTrue(DemoMode::enabled());
    }

    public function test_demo_mode_is_unavailable_in_production_even_when_the_flag_is_on(): void
    {
        config()->set('landtech.demo_mode', true);

        app()->detectEnvironment(fn() => 'production');

        try {
            $this->assertFalse(
                DemoMode::enabled(),
                'the environment gate must override the config flag'
            );

            // The welcome page must not render demo entry links.
            $this->get('/')
                ->assertOk()
                ->assertDontSee(route('demo.panel', ['role' => Role::CITIZEN]), false);
        } finally {
            app()->detectEnvironment(fn() => 'testing');
        }
    }

    public function test_demo_shortcuts_are_not_rendered_when_demo_mode_is_off(): void
    {
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertDontSee(route('demo.panel', ['role' => Role::CITIZEN]), false)
            ->assertDontSee(__('welcome.panels.enter_demo'));
    }

    public function test_demo_shortcuts_appear_when_demo_mode_is_on_and_accounts_exist(): void
    {
        $this->enableDemoMode();
        $this->seed();

        $this->get('/')
            ->assertOk()
            ->assertSee(route('demo.panel', ['role' => Role::CITIZEN]), false)
            ->assertSee(__('welcome.panels.enter_demo'));
    }

    public function test_missing_demo_accounts_are_reported_rather_than_invented(): void
    {
        $this->enableDemoMode();
        // Roles exist but NO demo accounts are seeded.
        $this->seedCitizenRoleOnly();

        $response = $this->get('/');

        $response->assertOk();

        // No shortcut is offered, and the page says so.
        $response->assertDontSee(route('demo.panel', ['role' => Role::CITIZEN]), false);
        $response->assertSee(__('welcome.panels.no_demo_account'));
    }

    // ---------------------------------------------------- demo entry route

    public function test_demo_entry_route_is_not_found_when_demo_mode_is_off(): void
    {
        $this->seed();

        $this->get(route('demo.panel', ['role' => Role::CITIZEN]))->assertNotFound();
    }

    public function test_demo_entry_route_is_not_found_in_production(): void
    {
        config()->set('landtech.demo_mode', true);
        $this->seed();

        app()->detectEnvironment(fn() => 'production');

        try {
            $this->get(route('demo.panel', ['role' => Role::REGISTRY_OFFICER]))->assertNotFound();
        } finally {
            app()->detectEnvironment(fn() => 'testing');
        }
    }

    public function test_demo_entry_rejects_a_role_that_is_not_an_implemented_panel(): void
    {
        $this->enableDemoMode();
        $this->seed();

        // `super_admin` is not implemented, so it is not a valid demo panel.
        $this->get('/demo/panel/super_admin')->assertNotFound();
    }

    public function test_demo_entry_logs_in_the_matching_demo_account_only(): void
    {
        $this->enableDemoMode();
        $this->seed();

        $response = $this->get(route('demo.panel', ['role' => Role::CITIZEN]));

        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertSame(
            DatabaseSeeder::DEMO_ACCOUNTS[Role::CITIZEN],
            $user->email,
            'demo entry must authenticate the configured demo account'
        );
        $this->assertTrue($user->hasRole(Role::CITIZEN));

        $response->assertRedirect(route('citizen.dashboard', absolute: false));
    }

    public function test_demo_entry_does_not_grant_a_role_to_the_account(): void
    {
        $this->enableDemoMode();
        $this->seed();

        $before = \Illuminate\Support\Facades\DB::table('role_user')->count();

        $this->get(route('demo.panel', ['role' => Role::BANK_OFFICER]));

        $this->assertAuthenticated();
        $this->assertSame(
            $before,
            \Illuminate\Support\Facades\DB::table('role_user')->count(),
            'demo entry must not create any role assignment'
        );

        // The signed-in account holds ONLY its own role.
        $this->assertSame(1, auth()->user()->roles()->count());
        $this->assertTrue(auth()->user()->hasRole(Role::BANK_OFFICER));
    }

    public function test_demo_entry_replaces_an_existing_session_rather_than_elevating_it(): void
    {
        $this->enableDemoMode();
        $this->seed();

        // Sign in as an ordinary citizen with no privileged role.
        $citizen = User::factory()->create(['email' => 'plain.user@example.test']);
        $this->actingAs($citizen);

        $this->get(route('demo.panel', ['role' => Role::ADMIN]));

        $this->assertAuthenticated();

        // The session is now the admin demo account — not the previous user,
        // and the previous user gained nothing.
        $this->assertSame(DatabaseSeeder::DEMO_ACCOUNTS[Role::ADMIN], auth()->user()->email);
        $this->assertFalse($citizen->fresh()->hasRole(Role::ADMIN));
        $this->assertSame(0, $citizen->fresh()->roles()->count());
    }

    public function test_demo_entry_does_not_bypass_role_authorization(): void
    {
        $this->enableDemoMode();
        $this->seed();

        // Enter as a citizen...
        $this->get(route('demo.panel', ['role' => Role::CITIZEN]));
        $this->assertAuthenticated();

        // ...and the citizen role must still be refused the officer panels.
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('registry.dashboard'))->assertForbidden();
        $this->get(route('bank.dashboard'))->assertForbidden();
    }

    public function test_demo_entry_cannot_be_used_without_demo_mode_even_when_authenticated(): void
    {
        $this->seed();

        $user = $this->userWithRole(Role::CITIZEN);

        $this->actingAs($user)
            ->get(route('demo.panel', ['role' => Role::ADMIN]))
            ->assertNotFound();
    }

    // ------------------------------------------------------------- panels UI

    public function test_all_five_role_cards_are_rendered(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        foreach (
            [
                'welcome.panels.super_admin.title',
                'welcome.panels.admin.title',
                'welcome.panels.registry_officer.title',
                'welcome.panels.bank_officer.title',
                'welcome.panels.citizen.title',
            ] as $key
        ) {
            $response->assertSee(__($key));
        }
    }

    public function test_super_admin_is_labelled_coming_soon_and_not_linked(): void
    {
        $this->enableDemoMode();
        $this->seed();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(__('welcome.panels.coming_soon'));

        // No demo shortcut or route for an unimplemented role.
        $response->assertDontSee('/demo/panel/super_admin', false);
        $this->assertArrayNotHasKey('super_admin', DemoMode::implementedPanels());
    }

    public function test_implemented_panels_are_reported_as_available(): void
    {
        $this->enableDemoMode();
        $this->seed();

        foreach (DemoMode::panels() as $panel) {
            if ($panel['key'] === 'super_admin') {
                $this->assertFalse($panel['available']);
                $this->assertTrue($panel['coming_soon']);
                continue;
            }

            $this->assertTrue($panel['available'], "{$panel['key']} should be available");
            $this->assertFalse($panel['coming_soon']);
        }
    }

    public function test_panel_cards_only_link_to_routes_that_exist(): void
    {
        $this->enableDemoMode();
        $this->seed();

        foreach (DemoMode::panels() as $panel) {
            if ($panel['available']) {
                $this->assertNotNull($panel['login_url']);
                $this->assertTrue(\Illuminate\Support\Facades\Route::has(
                    DemoMode::implementedPanels()[$panel['role']]
                ));
            } else {
                $this->assertNull($panel['login_url'], 'unavailable panels must not link anywhere');
            }
        }
    }

    // ---------------------------------------------------------- localisation

    public function test_welcome_page_renders_in_both_locales(): void
    {
        $this->get('/')->assertOk()->assertSee('Explore Demo Panels');

        $this->post(route('locale.switch'), ['locale' => 'bn']);

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="bn"', false)
            ->assertSee('ডেমো প্যানেল দেখুন', false);
    }

    public function test_welcome_translations_exist_in_both_locales(): void
    {
        $en = require base_path('lang/en/welcome.php');
        $bn = require base_path('lang/bn/welcome.php');

        $flatten = function (array $array, string $prefix = '') use (&$flatten): array {
            $out = [];
            foreach ($array as $key => $value) {
                $full = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                if (is_array($value)) {
                    $out += $flatten($value, $full);
                } else {
                    $out[$full] = (string) $value;
                }
            }

            return $out;
        };

        $enFlat = $flatten($en);
        $bnFlat = $flatten($bn);

        $this->assertSame([], array_keys(array_diff_key($enFlat, $bnFlat)), 'keys missing from bn/welcome.php');
        $this->assertSame([], array_keys(array_diff_key($bnFlat, $enFlat)), 'keys missing from en/welcome.php');

        // Bengali must be genuinely translated, not English passthrough.
        $untranslated = [];
        foreach ($enFlat as $key => $english) {
            if (! preg_match('/[A-Za-z]/', $english)) {
                continue;
            }
            if (($bnFlat[$key] ?? null) === $english) {
                $untranslated[] = $key;
            }
        }

        $this->assertSame([], $untranslated, 'Untranslated welcome keys: ' . implode(', ', $untranslated));
    }

    // ---------------------------------------------------------------- helpers

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

    /**
     * Create the citizen role only — no demo accounts — so the "missing demo
     * account" path can be exercised honestly.
     */
    protected function seedCitizenRoleOnly(): void
    {
        Role::query()->firstOrCreate(
            ['name' => Role::CITIZEN],
            ['label_en' => 'Citizen', 'label_bn' => 'নাগরিক']
        );
    }
}
