<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\PlotOwnership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Citizen/User Panel — self-service pages for ordinary account holders.
 *
 * HONESTY CONTRACT
 * ----------------
 * There is currently NO database link between a User and a Party
 * (see PartyPolicy::owns(), which conservatively returns false), and no
 * applications table exists yet. These controllers therefore read only what is
 * genuinely attributable to the authenticated user — which today is nothing —
 * and every list page renders a truthful empty state plus a next step.
 *
 * Nothing on these pages fabricates statistics, borrows the synthetic demo
 * dataset, or implies a records/claims workflow that does not exist.
 */
class CitizenPanelController extends Controller
{
    /**
     * Records genuinely linked to the authenticated user.
     *
     * Today this is intentionally empty: no users.party_id column exists and
     * no verified claim workflow links a user to a party. When that backend
     * lands, only this method needs to change.
     */
    protected function linkedParty(User $user): ?Party
    {
        return null;
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('citizen.dashboard', [
            'user' => $user,
            'emailVerified' => $user->email_verified_at !== null,
            'plotCount' => 0,
            'ownershipCount' => 0,
            'applicationCount' => 0,
            'unreadNotificationCount' => 0,
            'recentActivity' => collect(),
        ]);
    }

    public function land(Request $request): View
    {
        return view('citizen.land', [
            'records' => collect(),
            'backendReady' => false,
        ]);
    }

    public function deeds(Request $request): View
    {
        return view('citizen.deeds', [
            'records' => collect(),
            'backendReady' => false,
        ]);
    }

    public function ownership(Request $request): View
    {
        return view('citizen.ownership', [
            'records' => collect(),
            'backendReady' => false,
        ]);
    }

    public function applications(Request $request): View
    {
        return view('citizen.applications', [
            'records' => collect(),
            'backendReady' => false,
        ]);
    }

    public function notifications(Request $request): View
    {
        $user = $request->user();

        // Paginated so the payload stays bounded as the notifications table
        // grows, and the unread badge is a COUNT query rather than a second
        // full collection load. Ownership scope is unchanged: the relation is
        // still resolved from the authenticated user only.
        //
        // The `notifications` table does not exist in this phase, so the query
        // is guarded: we render the honest empty state rather than a 500. When
        // the table lands, this guard can be removed.
        if (! Schema::hasTable('notifications')) {
            return view('citizen.notifications', [
                'notifications' => new LengthAwarePaginator([], 0, 20),
                'unreadCount' => 0,
            ]);
        }

        return view('citizen.notifications', [
            'notifications' => $user->notifications()->paginate(20),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function help(): View
    {
        return view('citizen.help');
    }
}
