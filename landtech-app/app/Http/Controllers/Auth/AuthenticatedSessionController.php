<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Auth\LandingPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * POST-LOGIN DESTINATION POLICY (Phase 4 / H2)
     * --------------------------------------------
     * The destination is derived from the user's authorized roles via
     * LandingPage. A stored intended URL is honoured ONLY when it is not one of
     * the role-restricted dashboards — because such a URL is attacker-influenced
     * (requesting /citizen/dashboard while logged out plants it in the session)
     * and would otherwise drop an admin onto a 403 instead of their dashboard.
     *
     * This does NOT weaken authorization: the `role` middleware still guards
     * every role-specific route, so an honoured intended URL can never grant
     * access the user's roles do not already allow.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        $landingRoute = LandingPage::routeNameFor($user);
        $landingUrl = route($landingRoute, absolute: false);

        $intended = $request->session()->pull('url.intended');

        // Only a non-dashboard intended URL may override the role landing page.
        if ($this->intendedIsHonourable($intended, $landingUrl)) {
            return redirect()->to($intended);
        }

        return redirect()->to($landingUrl);
    }

    /**
     * Decide whether a stored intended URL may override the role landing page.
     *
     * Rejected when it is empty, when it is a role-restricted dashboard (any of
     * them, including the user's own — the equality check below already covers
     * that case), or when it is not a same-host, relative-safe path. The host
     * check is an explicit open-redirect defence: Laravel's own `intended()`
     * helper performs an equivalent check, and we are deliberately not relying
     * on it here because the value is consumed via `redirect()->to()`.
     */
    protected function intendedIsHonourable(?string $intended, string $landingUrl): bool
    {
        if (! is_string($intended) || $intended === '') {
            return false;
        }

        // Reject anything that is not a same-host URL. A protocol-relative or
        // absolute foreign URL must never be emitted.
        if (! $this->isSameHost($intended)) {
            return false;
        }

        // Compare paths, not full URLs, so host/port differences cannot fool us.
        $intendedPath = parse_url($intended, PHP_URL_PATH) ?: '/';

        if ($intendedPath === $landingUrl) {
            return false; // Already going there; avoid a pointless hop.
        }

        foreach (LandingPage::ROLES as $routeName) {
            if ($intendedPath === route($routeName, absolute: false)) {
                // It is some role's dashboard. Only allow it if it is THIS
                // user's own landing page, which the equality check above
                // already excluded — so never honour it.
                return false;
            }
        }

        return true;
    }

    /**
     * True when the intended URL resolves to the current application host.
     *
     * Rejects scheme-relative (`//evil.test`), absolute foreign and non-http
     * values; accepts app-relative paths such as `/profile`.
     */
    protected function isSameHost(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        // A path-only value (no scheme, no host) is same-origin by definition.
        if (! isset($parts['host'])) {
            // Guard against scheme-relative URLs, which parse to host-only.
            return ! str_starts_with($url, '//');
        }

        $scheme = $parts['scheme'] ?? '';

        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        return $parts['host'] === request()->getHost();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
