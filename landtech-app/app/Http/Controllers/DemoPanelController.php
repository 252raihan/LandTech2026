<?php

namespace App\Http\Controllers;

use App\Support\Demo\DemoMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Presentation-only demo entry point.
 *
 * WHAT THIS DOES
 * --------------
 * Logs the browser in as a PRE-SEED development demo account for a role, then
 * hands control to the normal post-login redirect so the user lands on that
 * role's real dashboard.
 *
 * WHAT THIS DELIBERATELY DOES NOT DO
 * ----------------------------------
 *  - It does not accept a role, role_id or any privilege value from the request.
 *    The role is a fixed, validated enum from DemoMode::implementedPanels().
 *  - It does not attach, detach or switch roles on any account. The demo account
 *    already holds its role from seeding; nothing is granted here.
 *  - It does not modify the currently authenticated user. Any existing session
 *    is logged out first, then the demo account is authenticated normally.
 *  - It does not bypass EnsureRole, policies or IdentityAccessService. The demo
 *    session is an ordinary session and is subject to every authorization check.
 *  - It does not use a token, magic link or query-parameter bypass. It performs
 *    a genuine Auth::loginUsingId() for a specifically configured demo account.
 *
 * A route parameter can only ever select WHICH configured demo account is used.
 * Because DemoMode::enabled() requires local/testing AND the config flag, and
 * because the account must exist in the database, this route is inert in
 * production and cannot be used to reach a privileged panel there.
 */
class DemoPanelController extends Controller
{
    /**
     * Enter a role panel using its seeded demo account.
     */
    public function enter(Request $request, string $role): RedirectResponse
    {
        // Gate 1: Demo Mode must be active (config flag AND local/testing).
        abort_unless(DemoMode::enabled(), 404);

        // Gate 2: the role must be one of the implemented panels. This is an
        // allow-list comparison against server-side definitions, so an arbitrary
        // role name in the URL can never select an unimplemented or privileged
        // role outside the list.
        abort_unless(
            array_key_exists($role, DemoMode::implementedPanels()),
            404,
            'No demo panel is available for this role.'
        );

        // Gate 3: a seeded demo account must actually exist for this role.
        $demoUser = DemoMode::accountFor($role);

        abort_if($demoUser === null, 404, 'No demo account is configured for this role.');

        // Drop any existing session before switching accounts, so we never
        // silently reuse or elevate a previously authenticated user.
        if (Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // A genuine authentication of the demo account. No credential is
        // accepted from the request, and no role is written.
        Auth::loginUsingId($demoUser->getKey());

        $request->session()->regenerate();

        // Normal role-aware landing resolution — the demo session is subject to
        // exactly the same redirect policy as a manual login.
        return redirect()
            ->route(\App\Support\Auth\LandingPage::routeNameFor($demoUser))
            ->with('demo_mode', true);
    }
}
