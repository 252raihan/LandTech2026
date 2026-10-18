<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\LandRecord;
use App\Models\Mouza;
use App\Models\SaleListing;
use App\Models\VerificationCheck;
use App\Models\VerificationRequest;
use App\Services\LandVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Phase 5 — Buyer: request ownership verification for ANY land.
 *
 * A buyer may request verification for land that has NO listing. The land
 * identifiers are therefore collected directly and stored on the request, and
 * `sale_listing_id` is optional.
 *
 * PRIVACY BOUNDARY
 *   Creating a verification request does NOT grant the buyer access to any
 *   seller's private documents. This controller never reads another user's
 *   `land_documents`; that path is gated by LandDocumentPolicy, which only the
 *   owner and a reviewer pass. If the referenced land belongs to another user,
 *   the buyer sees the REVIEW OUTCOME, never the underlying uploads.
 *
 * HONESTY
 *   The automated checks are run at submission time and recorded individually.
 *   Checks requiring an external authority are stored as `not_available` with an
 *   explicit limitation. The request is never labelled "Government Verified".
 */
class VerificationRequestController extends Controller
{
    public function __construct(protected LandVerificationService $verification) {}

    protected function ownedRequest(Request $request, int $id): VerificationRequest
    {
        return VerificationRequest::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VerificationRequest::class);

        $requests = VerificationRequest::query()
            ->where('user_id', $request->user()->id)
            ->withCount('checks')
            ->latest()
            ->paginate(10);

        return view('citizen.buyer.verification.index', [
            'requests' => $requests,
        ]);
    }

    /**
     * The verification request form.
     *
     * May be pre-filled from a published listing when the buyer arrived from
     * search, which is a convenience — never an authorization shortcut.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', VerificationRequest::class);

        $listing = null;

        if ($request->filled('listing_id')) {
            // Published listings only: a buyer cannot probe unpublished land.
            $listing = SaleListing::query()->published()->find($request->integer('listing_id'));
        }

        return view('citizen.buyer.verification.create', [
            'listing' => $listing,
            'prefill' => $this->prefillFromListing($listing),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', VerificationRequest::class);

        $validated = $request->validate([
            'sale_listing_id' => ['nullable', 'integer'],

            'district_name' => ['nullable', 'string', 'max:128'],
            'upazila_name' => ['nullable', 'string', 'max:128'],
            'mouza_name' => ['nullable', 'string', 'max:128'],
            'jl_no' => ['nullable', 'string', 'max:32'],
            'survey_type' => ['nullable', Rule::in(LandRecord::SURVEY_TYPES)],
            'khatian_no' => ['nullable', 'string', 'max:64'],
            'dag_no' => ['nullable', 'string', 'max:64'],
            'area' => ['nullable', 'numeric', 'min:0', 'max:9999'],

            'deed_no' => ['nullable', 'string', 'max:64'],
            'registration_office' => ['nullable', 'string', 'max:191'],

            // Required: mirrors the Phase 3 purpose requirement and keeps the
            // review meaningful.
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // At least one land identifier is needed, or the request is unactionable.
        if (empty($validated['khatian_no']) && empty($validated['dag_no']) && empty($validated['mouza_name'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'khatian_no' => __('citizen.verification.need_identifier'),
            ]);
        }

        $listingId = null;
        $landRecordId = null;
        $plotId = null;
        $mouzaId = null;

        if (! empty($validated['sale_listing_id'])) {
            // Only a PUBLISHED listing may be referenced, so a buyer cannot use
            // this endpoint to confirm the existence of unpublished land.
            $listing = SaleListing::query()->published()->find($validated['sale_listing_id']);

            if ($listing) {
                $listingId = $listing->id;
                $landRecordId = $listing->land_record_id;
                $plotId = $listing->plot_id;
                $mouzaId = $listing->mouza_id;
            }
        }

        // Resolve against our own records when possible. This is a best-effort
        // internal linkage, NOT proof that the land exists or is owned.
        if ($mouzaId === null && ! empty($validated['khatian_no']) && ! empty($validated['dag_no'])) {
            $match = \App\Models\Plot::query()
                ->where('khatian_no', $validated['khatian_no'])
                ->where('plot_no', $validated['dag_no'])
                ->first();

            if ($match) {
                $plotId = $match->id;
                $mouzaId = $match->mouza_id;
            }
        }

        $verificationRequest = DB::transaction(function () use (
            $request,
            $validated,
            $listingId,
            $landRecordId,
            $plotId,
            $mouzaId
        ) {
            $model = new VerificationRequest($validated);
            $model->user_id = $request->user()->id;
            $model->reference_no = $this->referenceNumber();
            $model->sale_listing_id = $listingId;
            $model->land_record_id = $landRecordId;
            $model->plot_id = $plotId;
            $model->mouza_id = $mouzaId;

            $model->status = VerificationRequest::STATUS_SUBMITTED;

            // There is no government integration. This is the honest default and
            // is set server-side so it can never be supplied by the client.
            $model->official_status = VerificationRequest::OFFICIAL_NOT_AVAILABLE;

            $model->submitted_at = now();
            $model->save();

            return $model;
        });

        // Run the automated internal checks immediately and record each one.
        $checks = $this->verification->runChecks($verificationRequest);

        $verificationRequest->risk_indicator = $this->verification->riskIndicator($checks);
        $verificationRequest->outcome_limitations = $this->verification->limitationsStatement();
        $verificationRequest->save();

        return redirect()
            ->route('citizen.verification.show', $verificationRequest)
            ->with('status', 'verification-request-submitted');
    }

    public function show(Request $request, int $id): View
    {
        $verificationRequest = $this->ownedRequest($request, $id);

        $this->authorize('view', $verificationRequest);

        return view('citizen.buyer.verification.show', [
            'request' => $verificationRequest->load(['checks', 'listing', 'reviewer']),
            'checkLabels' => VerificationCheck::CATALOGUE,
        ]);
    }

    /**
     * Supply additional information when a reviewer asks for it.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $verificationRequest = $this->ownedRequest($request, $id);

        $this->authorize('update', $verificationRequest);

        $validated = $request->validate([
            'additional_information' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        // Appended, not overwritten: the original reason is part of the record.
        $verificationRequest->review_note = trim(
            ($verificationRequest->review_note ?? '') . "\n\n[requester] " . $validated['additional_information']
        );
        $verificationRequest->status = VerificationRequest::STATUS_UNDER_REVIEW;
        $verificationRequest->save();

        return back()->with('status', 'verification-info-supplied');
    }

    /**
     * Re-run the automated internal checks. Still only our own data.
     */
    public function rerunChecks(Request $request, int $id): RedirectResponse
    {
        $verificationRequest = $this->ownedRequest($request, $id);

        $this->authorize('view', $verificationRequest);

        $checks = $this->verification->runChecks($verificationRequest);

        $verificationRequest->risk_indicator = $this->verification->riskIndicator($checks);
        $verificationRequest->save();

        return back()->with('status', 'verification-checks-refreshed');
    }

    /**
     * @return array<string, mixed>
     */
    protected function prefillFromListing(?SaleListing $listing): array
    {
        if (! $listing) {
            return [];
        }

        return [
            'sale_listing_id' => $listing->id,
            'district_name' => $listing->district_name,
            'upazila_name' => $listing->upazila_name,
            'mouza_name' => $listing->mouza_name,
            'jl_no' => $listing->jl_no,
            'survey_type' => $listing->survey_type,
            'khatian_no' => $listing->khatian_no,
            'dag_no' => $listing->dag_no,
            'area' => $listing->area,
        ];
    }

    protected function referenceNumber(): string
    {
        do {
            $reference = 'VER-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (VerificationRequest::query()->where('reference_no', $reference)->exists());

        return $reference;
    }
}
