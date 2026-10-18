<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\LandRecord;
use App\Models\PartyUserLink;
use App\Models\SaleListing;
use App\Models\SaleListing as Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Phase 5 — Seller: sale listings.
 *
 * THE VERIFICATION WALL
 *   `verification_status` is NEVER writable from this controller. There is no
 *   route, request field or code path here that sets it, and the policy's
 *   `setVerificationStatus` ability is reviewer-only. An owner can create a
 *   listing, submit it, and withdraw it — nothing more.
 *
 *   `status` is likewise constrained: an owner can reach `pending_review` and
 *   `withdrawn`. Reaching `published` or `sold` requires the reviewer ability,
 *   so an owner cannot self-publish or declare a sale.
 *
 * SHARE CAP
 *   A listing may not offer more than the land record's claimed share. The
 *   record's share is itself only a CLAIM, so this is a consistency check, not
 *   a proof of authority — and the listing carries an explicit unverified label
 *   until a reviewer says otherwise.
 */
class SaleListingController extends Controller
{
    protected function ownedListing(Request $request, int $id): SaleListing
    {
        return SaleListing::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SaleListing::class);

        $listings = SaleListing::query()
            ->where('user_id', $request->user()->id)
            ->withCount('interestRequests')
            ->latest()
            ->paginate(10);

        return view('citizen.seller.listings.index', [
            'listings' => $listings,
            'hasVerifiedLink' => $request->user()->hasVerifiedPartyLink(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SaleListing::class);

        return view('citizen.seller.listings.form', [
            'listing' => null,
            'records' => LandRecord::query()
                ->where('user_id', $request->user()->id)
                ->orderByDesc('id')
                ->get(),
            'hasVerifiedLink' => $request->user()->hasVerifiedPartyLink(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', SaleListing::class);

        $validated = $this->validateListing($request);

        // Owner-scoped: the land record must belong to this user.
        $record = LandRecord::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['land_record_id']);

        $this->assertShareWithinClaim($record, $validated);

        $listing = DB::transaction(function () use ($request, $record, $validated) {
            $listing = new SaleListing($this->listingAttributes($record, $validated));
            $listing->user_id = $request->user()->id;
            $listing->reference_no = $this->referenceNumber();
            $listing->land_record_id = $record->id;

            // Verification always starts unverified and is reviewer-controlled.
            $listing->verification_status = SaleListing::VERIFICATION_UNVERIFIED;

            $this->applyOwnerLifecycle($listing, $request);

            $listing->save();

            return $listing;
        });

        return redirect()
            ->route('citizen.listings.show', $listing)
            ->with('status', $listing->status === SaleListing::STATUS_DRAFT
                ? 'listing-draft-saved'
                : 'listing-submitted-for-review');
    }

    public function show(Request $request, int $id): View
    {
        $listing = $this->ownedListing($request, $id);

        $this->authorize('view', $listing);

        return view('citizen.seller.listings.show', [
            'listing' => $listing->load(['landRecord', 'reviewer', 'interestRequests.user']),
        ]);
    }

    public function edit(Request $request, int $id): View
    {
        $listing = $this->ownedListing($request, $id);

        $this->authorize('update', $listing);

        return view('citizen.seller.listings.form', [
            'listing' => $listing,
            'records' => LandRecord::query()
                ->where('user_id', $request->user()->id)
                ->orderByDesc('id')
                ->get(),
            'hasVerifiedLink' => $request->user()->hasVerifiedPartyLink(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $listing = $this->ownedListing($request, $id);

        $this->authorize('update', $listing);

        $validated = $this->validateListing($request);

        $record = LandRecord::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['land_record_id']);

        $this->assertShareWithinClaim($record, $validated);

        DB::transaction(function () use ($request, $listing, $record, $validated) {
            $listing->fill($this->listingAttributes($record, $validated));
            $listing->land_record_id = $record->id;

            // Defensive: an update must never alter verification state, even if
            // the fillable list were to change in future.
            $listing->verification_status = $listing->getOriginal('verification_status')
                ?? SaleListing::VERIFICATION_UNVERIFIED;

            $this->applyOwnerLifecycle($listing, $request);

            $listing->save();
        });

        return redirect()
            ->route('citizen.listings.show', $listing)
            ->with('status', 'listing-updated');
    }

    /**
     * Submit a draft for review. Reaches `pending_review` only — never
     * `published`, which is a reviewer decision.
     */
    public function submit(Request $request, int $id): RedirectResponse
    {
        $listing = $this->ownedListing($request, $id);

        $this->authorize('submit', $listing);

        $listing->status = SaleListing::STATUS_PENDING_REVIEW;
        $listing->submitted_at = now();
        $listing->save();

        return back()->with('status', 'listing-submitted-for-review');
    }

    /**
     * Withdraw a live listing. The owner may do this without review.
     */
    public function withdraw(Request $request, int $id): RedirectResponse
    {
        $listing = $this->ownedListing($request, $id);

        $this->authorize('withdraw', $listing);

        $listing->status = SaleListing::STATUS_WITHDRAWN;
        $listing->save();

        return back()->with('status', 'listing-withdrawn');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $listing = $this->ownedListing($request, $id);

        $this->authorize('delete', $listing);

        $listing->delete();

        return redirect()
            ->route('citizen.listings.index')
            ->with('status', 'listing-deleted');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateListing(Request $request): array
    {
        return $request->validate([
            'land_record_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:191'],
            'public_description' => ['nullable', 'string', 'max:5000'],
            'area' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'area_unit' => ['required', Rule::in(LandRecord::AREA_UNITS)],
            'asking_price' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'price_unit' => ['required', Rule::in(SaleListing::PRICE_UNITS)],
            'sale_type' => ['required', Rule::in([SaleListing::SALE_FULL_PLOT, SaleListing::SALE_PARTIAL])],
            'claim_share_numerator' => ['required', 'integer', 'min:1', 'max:1000000'],
            'claim_share_denominator' => ['required', 'integer', 'min:1', 'max:1000000', 'gte:claim_share_numerator'],
            'contact_preference' => ['required', Rule::in([
                SaleListing::CONTACT_VIA_PLATFORM,
                SaleListing::CONTACT_PHONE,
                SaleListing::CONTACT_EMAIL,
            ])],
            'action' => ['nullable', Rule::in(['draft', 'submit'])],
        ]);
    }

    /**
     * Deny an offer larger than the share the land record claims.
     *
     * Compared as exact fractions (cross-multiplied) to avoid float error.
     */
    protected function assertShareWithinClaim(LandRecord $record, array $data): void
    {
        $claimN = (int) $record->claim_share_numerator;
        $claimD = max(1, (int) $record->claim_share_denominator);

        $offerN = (int) $data['claim_share_numerator'];
        $offerD = max(1, (int) $data['claim_share_denominator']);

        // offer <= claim  <=>  offerN * claimD <= claimN * offerD
        if (($offerN * $claimD) > ($claimN * $offerD)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'claim_share_numerator' => __('citizen.listings.share_exceeds_claim', [
                    'claim' => $claimN . '/' . $claimD,
                ]),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function listingAttributes(LandRecord $record, array $data): array
    {
        return [
            'mouza_id' => $record->mouza_id,
            'plot_id' => $record->plot_id,
            'district_name' => $record->district_name,
            'upazila_name' => $record->upazila_name,
            'mouza_name' => $record->mouza_name,
            'jl_no' => $record->jl_no,
            'survey_type' => $record->survey_type,
            'khatian_no' => $record->khatian_no,
            'dag_no' => $record->dag_no,
            'title' => $data['title'],
            'public_description' => $data['public_description'] ?? null,
            'area' => $data['area'] ?? $record->area,
            'area_unit' => $data['area_unit'],
            'asking_price' => $data['asking_price'] ?? null,
            'price_unit' => $data['price_unit'],
            'sale_type' => $data['sale_type'],
            'claim_share_numerator' => $data['claim_share_numerator'],
            'claim_share_denominator' => $data['claim_share_denominator'],
            'contact_preference' => $data['contact_preference'],
        ];
    }

    /**
     * Owners may reach draft or pending_review. Nothing else.
     */
    protected function applyOwnerLifecycle(SaleListing $listing, Request $request): void
    {
        if ($request->input('action') === 'submit') {
            $listing->status = SaleListing::STATUS_PENDING_REVIEW;
            $listing->submitted_at = $listing->submitted_at ?? now();

            return;
        }

        // Never downgrade a listing that has already gone to review.
        if ($listing->exists && ! $listing->isEditableByOwner()) {
            return;
        }

        $listing->status = SaleListing::STATUS_DRAFT;
    }

    protected function referenceNumber(): string
    {
        do {
            $reference = 'LST-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (Listing::query()->where('reference_no', $reference)->exists());

        return $reference;
    }
}
