<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Support\Demo\DemoMode;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end walkthrough of the presentation flow, in an ISOLATED test
 * database: seed the demo accounts, load the landing page as a browser would,
 * follow each "Enter demo panel" shortcut, and confirm the resulting session
 * lands on the right dashboard with the right authorization.
 *
 * This mirrors what the presenter does live, so a regression here would be
 * visible on stage.
 */
class DemoWalkthroughTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('landtech.demo_mode', true);
        $this->seed();
    }

    public static function panelProvider(): array
    {
        return [
            'admin' => [Role::ADMIN, 'admin.dashboard'],
            'registry officer' => [Role::REGISTRY_OFFICER, 'registry.dashboard'],
            'bank officer' => [Role::BANK_OFFICER, 'bank.dashboard'],
            'citizen' => [Role::CITIZEN, 'citizen.dashboard'],
        ];
    }

    /**
     * The demo-panel route keys (role names as they appear in the URL), which
     * are the actual role identifiers, not the human-readable data-provider
     * labels above.
     *
     * @return list<string>
     */
    protected function demoPanelKeys(): array
    {
        return array_keys(DemoMode::implementedPanels());
    }

    public function test_landing_page_offers_a_shortcut_for_every_seeded_panel(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(__('welcome.panels.enter_demo'));

        foreach ($this->demoPanelKeys() as $role) {
            $response->assertSee(route('demo.panel', ['role' => $role]), false);
        }

        // Super Admin is not implemented, so it must NOT be offered.
        $response->assertDontSee('/demo/panel/super_admin', false);
        $response->assertSee(__('welcome.panels.coming_soon'));

        // No "missing account" warning when everything is seeded.
        $response->assertDontSee(__('welcome.panels.missing_accounts', ['roles' => 'x']));
    }

    /**
     * @dataProvider panelProvider
     */
    public function test_following_a_panel_shortcut_lands_on_that_roles_dashboard(
        string $roleName,
        string $dashboardRoute
    ): void {
        // 1. Presenter loads the landing page.
        $this->get('/')->assertOk();

        // 2. Presenter clicks "Enter demo panel".
        $response = $this->get(route('demo.panel', ['role' => $roleName]));

        // 3. Landed on the right dashboard.
        $response->assertRedirect(route($dashboardRoute, absolute: false));

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole($roleName));

        // 4. The dashboard actually renders for that role.
        $this->get(route($dashboardRoute))->assertOk();
    }

    /**
     * @dataProvider panelProvider
     */
    public function test_a_demo_panel_cannot_reach_another_roles_dashboard(
        string $roleName,
        string $dashboardRoute
    ): void {
        $this->get(route('demo.panel', ['role' => $roleName]));
        $this->assertAuthenticated();

        foreach (self::panelProvider() as $otherRole => [$otherName, $otherRoute]) {
            if ($otherRoute === $dashboardRoute) {
                continue;
            }

            $this->get(route($otherRoute))
                ->assertForbidden();
        }
    }

    public function test_presenter_can_switch_between_panels_in_one_session(): void
    {
        // Enter as citizen.
        $this->get(route('demo.panel', ['role' => Role::CITIZEN]));
        $this->assertTrue(auth()->user()->hasRole(Role::CITIZEN));

        // Switch to registry officer in the same browser session.
        $this->get(route('demo.panel', ['role' => Role::REGISTRY_OFFICER]));

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole(Role::REGISTRY_OFFICER));
        $this->assertFalse(
            auth()->user()->hasRole(Role::CITIZEN),
            'switching panels must not accumulate roles'
        );

        $this->get(route('registry.dashboard'))->assertOk();
        $this->get(route('citizen.dashboard'))->assertForbidden();
    }

    public function test_demo_mode_on_does_not_alter_the_public_modules(): void
    {
        foreach (['dashboard', 'gis-map', 'audit-ledger', 'risk-scoring'] as $slug) {
            $this->get("/{$slug}")->assertOk();
        }
    }

    public function test_demo_mode_on_still_requires_authentication_for_panels(): void
    {
        // Directly requesting a dashboard without entering demo mode must fail.
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('citizen.dashboard'))->assertRedirect(route('login'));
    }
}
