<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Officer and admin landing dashboards (Phase 4).
 *
 * HONESTY CONTRACT
 * ----------------
 * The registry, bank and admin operational dashboards are NOT implemented yet.
 * These controllers therefore render an explicitly labelled placeholder that
 * states the dashboard is pending — they do not fabricate statistics, borrow
 * the synthetic demo dataset, or imply a workflow that does not exist.
 *
 * They exist now because the post-login redirect policy (see
 * App\Support\Auth\LandingPage) must send each role to a real, named route.
 * Replacing the view body is all that will be needed when each dashboard lands.
 */
class OfficerDashboardController extends Controller
{
    /**
     * Registry Officer dashboard — role:registry_officer.
     */
    public function registry(Request $request): View
    {
        return view('officer.pending', [
            'role' => \App\Models\Role::REGISTRY_OFFICER,
            'titleKey' => 'officer.registry.title',
            'subtitleKey' => 'officer.registry.subtitle',
        ]);
    }

    /**
     * Bank Officer dashboard — role:bank_officer.
     */
    public function bank(Request $request): View
    {
        return view('officer.pending', [
            'role' => \App\Models\Role::BANK_OFFICER,
            'titleKey' => 'officer.bank.title',
            'subtitleKey' => 'officer.bank.subtitle',
        ]);
    }

    /**
     * Admin operational dashboard — role:admin.
     */
    public function admin(Request $request): View
    {
        return view('officer.pending', [
            'role' => \App\Models\Role::ADMIN,
            'titleKey' => 'officer.admin.title',
            'subtitleKey' => 'officer.admin.subtitle',
        ]);
    }

    /**
     * Landing page for an authenticated account that holds no recognized role.
     *
     * Deliberately a distinct, honest page rather than a silent redirect to the
     * public demo dashboard: a role-less account is a provisioning problem that
     * should be visible, not hidden behind a public page.
     */
    public function pending(Request $request): View
    {
        return view('account.pending');
    }
}
