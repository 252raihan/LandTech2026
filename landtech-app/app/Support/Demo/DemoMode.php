<?php

namespace App\Support\Demo;

use App\Models\Role;
use App\Models\User;

/**
 * Central definition of the presentation Demo Mode.
 *
 * SAFETY MODEL
 * ------------
 * Demo Mode is a PRESENTATION AFFORDANCE, not an authorization mechanism.
 * It can do exactly one thing: render a clearly labelled link on the public
 * Welcome page that submits the login form for a pre-seeded DEVELOPMENT demo
 * account. Everything downstream is the normal stack:
 *
 *   - the credentials are real credentials, checked by the standard
 *     LoginRequest / Auth::attempt flow (rate limiting included);
 *   - the resulting session carries whatever roles that account genuinely has;
 *   - `EnsureRole`, the policies and IdentityAccessService are untouched, so a
 *     demo account sees only what its role legitimately permits;
 *   - no role is ever granted, revoked or switched by Demo Mode.
 *
 * Two independent gates must both pass before Demo Mode is active: the
 * `landtech.demo_mode` config flag AND a local/testing environment. The config
 * flag alone can never activate it, so a stray value in a production .env is
 * inert.
 */
class DemoMode
{
    /**
     * Is Demo Mode active in this environment?
     */
    public static function enabled(): bool
    {
        if (! config('landtech.demo_mode', false)) {
            return false;
        }

        return app()->environment(['local', 'testing']);
    }

    /**
     * Roles that have an implemented dashboard route, in presentation order.
     *
     * `super_admin` is deliberately absent: the role does not exist yet, so the
     * Welcome page labels it "Coming Soon" rather than linking a broken route.
     *
     * @return array<string, string> role name => route name
     */
    public static function implementedPanels(): array
    {
        return [
            Role::ADMIN => 'admin.dashboard',
            Role::REGISTRY_OFFICER => 'registry.dashboard',
            Role::BANK_OFFICER => 'bank.dashboard',
            Role::CITIZEN => 'citizen.dashboard',
        ];
    }

    /**
     * The demo account email for a role, if one is configured.
     */
    public static function accountEmail(string $role): ?string
    {
        $email = config("landtech.demo_accounts.{$role}");

        return is_string($email) && $email !== '' ? $email : null;
    }

    /**
     * Resolve the seeded demo user for a role.
     *
     * Returns null when no account is configured or the account does not exist.
     * Callers must treat null as "demo entry unavailable" and say so honestly —
     * never fabricate an account or fall back to some other user.
     */
    public static function accountFor(string $role): ?User
    {
        $email = self::accountEmail($role);

        if ($email === null) {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }

    /**
     * True when a one-click demo entry is genuinely possible for this role:
     * the dashboard route exists, the role is implemented, and the demo account
     * is actually present in the database.
     */
    public static function canEnter(string $role): bool
    {
        if (! self::enabled()) {
            return false;
        }

        if (! array_key_exists($role, self::implementedPanels())) {
            return false;
        }

        return self::accountFor($role) !== null;
    }

    /**
     * Presentation metadata for every panel card on the Welcome page.
     *
     * `available` is the single source of truth for whether the card renders a
     * working entry point or a "Coming Soon" label. It is computed from real
     * state (route existence + demo account presence), never hardcoded.
     *
     * @return list<array{
     *     key: string,
     *     role: string|null,
     *     title: string,
     *     description: string,
     *     icon: string,
     *     available: bool,
     *     coming_soon: bool,
     *     can_enter: bool,
     *     login_url: string|null
     * }>
     */
    public static function panels(): array
    {
        $definitions = [
            [
                'key' => 'super_admin',
                'role' => null, // not implemented — no role constant exists yet
                'title' => 'welcome.panels.super_admin.title',
                'description' => 'welcome.panels.super_admin.description',
                'icon' => 'shield-check',
            ],
            [
                'key' => 'admin',
                'role' => Role::ADMIN,
                'title' => 'welcome.panels.admin.title',
                'description' => 'welcome.panels.admin.description',
                'icon' => 'settings',
            ],
            [
                'key' => 'registry_officer',
                'role' => Role::REGISTRY_OFFICER,
                'title' => 'welcome.panels.registry_officer.title',
                'description' => 'welcome.panels.registry_officer.description',
                'icon' => 'document',
            ],
            [
                'key' => 'bank_officer',
                'role' => Role::BANK_OFFICER,
                'title' => 'welcome.panels.bank_officer.title',
                'description' => 'welcome.panels.bank_officer.description',
                'icon' => 'banknotes',
            ],
            [
                'key' => 'citizen',
                'role' => Role::CITIZEN,
                'title' => 'welcome.panels.citizen.title',
                'description' => 'welcome.panels.citizen.description',
                'icon' => 'user',
            ],
        ];

        return array_map(function (array $panel): array {
            $role = $panel['role'];

            // A panel is "available" only when its role is implemented AND its
            // dashboard route genuinely exists. Route existence is checked, not
            // assumed, so a card can never link to a missing route.
            $routeName = $role !== null ? (self::implementedPanels()[$role] ?? null) : null;
            $available = $routeName !== null && \Illuminate\Support\Facades\Route::has($routeName);

            return $panel + [
                'available' => $available,
                'coming_soon' => ! $available,
                'can_enter' => $role !== null && $available && self::canEnter($role),
                'login_url' => $available ? route('login') : null,
            ];
        }, $definitions);
    }
}
