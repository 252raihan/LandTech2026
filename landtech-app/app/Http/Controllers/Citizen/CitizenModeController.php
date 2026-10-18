<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 5 — Citizen mode selection.
 *
 * MODES ARE NOT ROLES.
 *   "Landowner / Seller" and "Land Buyer" are two VIEWS of the same `citizen`
 *   role. Selecting a mode writes a session preference and nothing else:
 *
 *     - it never touches `role_user`
 *     - it never grants a permission
 *     - every route behind both modes still requires the `citizen` role
 *
 *   The session value is a navigation hint only. Authorization is decided
 *   entirely by the policies, so tampering with the session value changes which
 *   menu is highlighted and nothing more.
 */
class CitizenModeController extends Controller
{
    public const MODE_SELLER = 'seller';
    public const MODE_BUYER = 'buyer';

    public const SESSION_KEY = 'citizen.mode';

    /**
     * The two modes, in display order.
     *
     * @return list<string>
     */
    public static function modes(): array
    {
        return [self::MODE_SELLER, self::MODE_BUYER];
    }

    /**
     * Resolve the active mode from the session, defaulting to seller.
     */
    public static function activeMode(Request $request): string
    {
        $mode = $request->session()->get(self::SESSION_KEY);

        return in_array($mode, self::modes(), true) ? $mode : self::MODE_SELLER;
    }

    /**
     * The mode-selection landing screen.
     *
     * Shows both mode cards plus a truthful summary of the account's actual
     * position: whether a verified party link exists, how many land records are
     * pending, and so on. Nothing here is invented.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('citizen.mode-select', [
            'user' => $user,
            'activeMode' => self::activeMode($request),
            'hasVerifiedLink' => $user->hasVerifiedPartyLink(),
            'pendingLink' => $user->partyLinks()
                ->where('status', \App\Models\PartyUserLink::STATUS_PENDING)
                ->exists(),
            'landRecordCount' => $user->landRecords()->count(),
            'pendingVerificationCount' => $user->landRecords()
                ->whereIn('status', [
                    \App\Models\LandRecord::STATUS_PENDING_VERIFICATION,
                    \App\Models\LandRecord::STATUS_UNDER_REVIEW,
                ])
                ->count(),
            'publishedListingCount' => $user->saleListings()->published()->count(),
            'openVerificationCount' => $user->verificationRequests()
                ->whereIn('status', [
                    \App\Models\VerificationRequest::STATUS_SUBMITTED,
                    \App\Models\VerificationRequest::STATUS_UNDER_REVIEW,
                    \App\Models\VerificationRequest::STATUS_ADDITIONAL_INFO_REQUIRED,
                ])
                ->count(),
            'interestCount' => $user->buyerInterestRequests()->count(),
        ]);
    }

    /**
     * Switch the active mode. Session-only; grants nothing.
     */
    public function switch(Request $request)
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', \Illuminate\Validation\Rule::in(self::modes())],
        ]);

        $request->session()->put(self::SESSION_KEY, $validated['mode']);

        // Explicitly assert that no authorization state changed. This is a
        // deliberate, testable no-op.
        // (role_user is untouched — see CitizenModeTest.)

        if ($request->expectsJson()) {
            return response()->json([
                'mode' => $validated['mode'],
                'message' => __('citizen.mode.switched'),
            ]);
        }

        return back()->with('mode_switched', $validated['mode']);
    }
}
