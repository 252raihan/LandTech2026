<?php

namespace App\Support\Auth;

use App\Models\Role;
use App\Models\User;

/**
 * Single source of truth for "where does this user land after login?".
 *
 * WHY THIS EXISTS
 * ---------------
 * The post-login destination must be derived from the user's authorized roles,
 * never from a stored intended URL. A stored intended URL is attacker-influenced
 * (anyone can request /citizen/dashboard while logged out to plant it in the
 * session), so honouring it blindly would bounce an admin into a 403 instead of
 * their own dashboard.
 *
 * ROLE PRECEDENCE (documented, deterministic)
 * -------------------------------------------
 * When a user holds several roles we resolve a single landing page using this
 * fixed order, highest privilege first:
 *
 *   1. admin            -> admin dashboard      (route: admin.dashboard)
 *   2. registry_officer -> registry dashboard   (route: registry.dashboard)
 *   3. bank_officer     -> bank dashboard       (route: bank.dashboard)
 *   4. citizen          -> citizen dashboard    (route: citizen.dashboard)
 *
 * The public `/dashboard` demo route is deliberately NOT a landing page for any
 * role: it stays a public demonstration surface, so no authenticated role is
 * silently dropped onto it.
 * This is a LANDING PAGE preference only — it is NOT a role hierarchy and it
 * grants nothing. Every role's routes remain independently gated by the `role`
 * middleware, so holding `admin` does not confer registry or citizen access.
 *
 * Only roles that are actually implemented appear here. `super_admin` does not
 * exist yet (Phase 4 scope), so it is deliberately absent.
 */
class LandingPage
{
    /**
     * Landing route name for each implemented role, in precedence order.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        Role::ADMIN => 'admin.dashboard',
        Role::REGISTRY_OFFICER => 'registry.dashboard',
        Role::BANK_OFFICER => 'bank.dashboard',
        Role::CITIZEN => 'citizen.dashboard',
    ];

    /**
     * Route name a user with NO recognized role is sent to.
     *
     * Deliberately NOT the public demo dashboard: silently dropping a
     * role-less account onto a public page hides a real provisioning problem.
     * `account.pending` explains the situation honestly instead.
     */
    public const NO_ROLE_ROUTE = 'account.pending';

    /**
     * Resolve the landing route NAME for a user (never a URL, so callers cannot
     * accidentally compare paths).
     */
    public static function routeNameFor(?User $user): string
    {
        if (! $user) {
            return 'login';
        }

        $held = $user->roles()->pluck('name')->all();

        foreach (self::ROLES as $role => $routeName) {
            if (in_array($role, $held, true)) {
                return $routeName;
            }
        }

        return self::NO_ROLE_ROUTE;
    }

    /**
     * True when the given route name is one of the role-restricted landing
     * dashboards. Used to decide whether a stored intended URL may be honoured:
     * a user may only be sent to a dashboard their own roles authorize.
     */
    public static function isRoleRestrictedDashboard(?string $routeName): bool
    {
        return $routeName !== null && in_array($routeName, self::ROLES, true);
    }
}
