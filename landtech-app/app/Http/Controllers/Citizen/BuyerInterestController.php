<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\BuyerInterestRequest;
use App\Models\SaleListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Phase 5 — Buyer interest and purchase enquiries.
 *
 * WHAT THIS IS
 *   An enquiry record. It creates NO contract, NO reservation of title, NO
 *   payment obligation and NO transfer. The UI states this explicitly, and no
 *   code path here changes a listing's status to `sold` or alters ownership.
 *
 * VISIBILITY
 *   Two separate list views, each authorized by BuyerInterestPolicy:
 *     - as buyer   → only enquiries THIS user raised
 *     - as owner   → only enquiries against THIS user's listings
 *   There is no combined view and no ID-based read that bypasses the policy.
 */
class BuyerInterestController extends Controller
{
    /**
     * Enquiries this user raised, as a buyer.
     */
    public function myRequests(Request $request): View
    {
        $this->authorize('viewAny', BuyerInterestRequest::class);

        $interests = BuyerInterestRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('listing')
            ->latest()
            ->paginate(10);

        return view('citizen.buyer.interest.index', [
            'interests' => $interests,
        ]);
    }

    /**
     * Enquiries received against this user's own listings, as an owner.
     */
    public function received(Request $request): View
    {
        $this->authorize('viewAny', BuyerInterestRequest::class);

        $listingIds = SaleListing::query()
            ->where('user_id', $request->user()->id)
            ->pluck('id');

        $interests = BuyerInterestRequest::query()
            ->whereIn('sale_listing_id', $listingIds)
            ->with(['listing', 'user'])
            ->latest()
            ->paginate(10);

        return view('citizen.seller.interest.index', [
            'interests' => $interests,
        ]);
    }

    /**
     * Express interest in a published listing.
     */
    public function store(Request $request, int $listingId): RedirectResponse
    {
        // Published only: a buyer cannot enquire about unpublished land, which
        // would otherwise leak the existence of a private listing.
        $listing = SaleListing::query()->published()->findOrFail($listingId);

        $this->authorize('create', [BuyerInterestRequest::class, $listing->user_id]);

        // A seller may not enquire about their own listing.
        if ($listing->user_id === $request->user()->id) {
            abort(403, __('citizen.interest.cannot_enquire_own_listing'));
        }

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        // Unique(sale_listing_id, user_id) prevents duplicates; surface that as
        // a friendly redirect rather than a 500.
        $existing = BuyerInterestRequest::query()
            ->where('sale_listing_id', $listing->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($existing) {
            return back()->with('status', 'interest-already-raised');
        }

        $interest = BuyerInterestRequest::create([
            'reference_no' => $this->referenceNumber(),
            'sale_listing_id' => $listing->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'] ?? null,
            'status' => BuyerInterestRequest::STATUS_SUBMITTED,
            // Contact details are never copied here; the owner decides.
            'contact_shared' => false,
        ]);

        return redirect()
            ->route('citizen.interest.show', $interest)
            ->with('status', 'interest-submitted');
    }

    /**
     * A single enquiry. Authorized for the buyer, the listing owner, or a
     * reviewer — nobody else.
     */
    public function show(Request $request, BuyerInterestRequest $interest): View
    {
        $this->authorize('view', $interest);

        return view('citizen.buyer.interest.show', [
            'interest' => $interest->load(['listing', 'user']),
            'isListingOwner' => $interest->listing?->user_id === $request->user()->id,
        ]);
    }

    /**
     * The listing owner responds: acknowledge, share contact, propose a visit,
     * or decline.
     *
     * This does NOT transfer anything. It records a reply.
     */
    public function respond(Request $request, BuyerInterestRequest $interest): RedirectResponse
    {
        $this->authorize('respond', $interest);

        $validated = $request->validate([
            'seller_response' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', \Illuminate\Validation\Rule::in([
                BuyerInterestRequest::STATUS_ACKNOWLEDGED,
                BuyerInterestRequest::STATUS_CONTACT_SHARED,
                BuyerInterestRequest::STATUS_SITE_VISIT_PROPOSED,
                BuyerInterestRequest::STATUS_DECLINED,
                BuyerInterestRequest::STATUS_CLOSED,
            ])],
            'contact_shared' => ['nullable', 'boolean'],
        ]);

        $interest->seller_response = $validated['seller_response'] ?? null;
        $interest->status = $validated['status'];
        $interest->responded_at = now();
        $interest->contact_shared = (bool) ($validated['contact_shared'] ?? false);
        $interest->save();

        return back()->with('status', 'interest-responded');
    }

    protected function referenceNumber(): string
    {
        do {
            $reference = 'INT-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (BuyerInterestRequest::query()->where('reference_no', $reference)->exists());

        return $reference;
    }
}
