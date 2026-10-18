<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreLandRecordRequest;
use App\Http\Requests\Citizen\UpdateLandRecordRequest;
use App\Models\District;
use App\Models\LandRecord;
use App\Models\Mouza;
use App\Models\PartyUserLink;
use App\Models\Upazila;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Phase 5 — Landowner: add and manage own land.
 *
 * AUTHORIZATION
 *   Every method resolves the record through the owner-scoped query below, so a
 *   citizen can never reach another citizen's record even by guessing an ID. The
 *   policy is then checked explicitly. Both layers are deliberate: the scoped
 *   query prevents enumeration, the policy documents and enforces the rule.
 *
 * HONESTY
 *   A submission is a CLAIM. New records enter `pending_verification` and no
 *   code path here ever sets `verified` — that belongs to the review workflow.
 */
class CitizenLandRecordController extends Controller
{
    /**
     * Owner-scoped lookup. Never returns another user's record.
     */
    protected function ownedRecord(Request $request, int $id): LandRecord
    {
        return LandRecord::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LandRecord::class);

        $records = LandRecord::query()
            ->where('user_id', $request->user()->id)
            ->with(['documents', 'listings'])
            ->latest()
            ->paginate(10);

        return view('citizen.seller.land.index', [
            'records' => $records,
            'hasVerifiedLink' => $request->user()->hasVerifiedPartyLink(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', LandRecord::class);

        return view('citizen.seller.land.form', [
            'record' => null,
            'districts' => District::query()->orderBy('name_en')->get(),
            'upazilas' => collect(),
            'mouzas' => collect(),
            'hasVerifiedLink' => $request->user()->hasVerifiedPartyLink(),
        ]);
    }

    public function store(StoreLandRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', LandRecord::class);

        $record = DB::transaction(function () use ($request) {
            $record = new LandRecord($request->recordAttributes());
            $record->user_id = $request->user()->id;

            // Attach the party only when a VERIFIED link exists. A pending claim
            // must never confer ownership attribution.
            $record->party_id = $this->verifiedPartyId($request);

            $this->applyLifecycle($record, $request);

            $record->save();

            return $record;
        });

        return redirect()
            ->route('citizen.land.show', $record)
            ->with('status', $record->isDraft() ? 'land-draft-saved' : 'land-submitted');
    }

    public function show(Request $request, int $id): View
    {
        $record = $this->ownedRecord($request, $id);

        $this->authorize('view', $record);

        return view('citizen.seller.land.show', [
            'record' => $record->load(['documents', 'listings', 'mouza.upazila.district', 'reviewer']),
            'hasVerifiedLink' => $request->user()->hasVerifiedPartyLink(),
        ]);
    }

    public function edit(Request $request, int $id): View
    {
        $record = $this->ownedRecord($request, $id);

        $this->authorize('update', $record);

        return view('citizen.seller.land.form', [
            'record' => $record,
            'districts' => District::query()->orderBy('name_en')->get(),
            'upazilas' => $record->mouza?->upazila
                ? Upazila::query()->where('district_id', $record->mouza->upazila->district_id)->orderBy('name_en')->get()
                : collect(),
            'mouzas' => $record->mouza?->upazila_id
                ? Mouza::query()->where('upazila_id', $record->mouza->upazila_id)->orderBy('name_en')->get()
                : collect(),
            'hasVerifiedLink' => $request->user()->hasVerifiedPartyLink(),
        ]);
    }

    public function update(UpdateLandRecordRequest $request, int $id): RedirectResponse
    {
        $record = $this->ownedRecord($request, $id);

        $this->authorize('update', $record);

        DB::transaction(function () use ($request, $record) {
            $record->fill($request->recordAttributes());
            $record->party_id = $this->verifiedPartyId($request) ?? $record->party_id;

            $this->applyLifecycle($record, $request);

            $record->save();
        });

        return redirect()
            ->route('citizen.land.show', $record)
            ->with('status', $record->isDraft() ? 'land-draft-saved' : 'land-submitted');
    }

    /**
     * Submit a draft for review. Moves to pending_verification — never verified.
     */
    public function submit(Request $request, int $id): RedirectResponse
    {
        $record = $this->ownedRecord($request, $id);

        $this->authorize('submit', $record);

        $record->status = LandRecord::STATUS_PENDING_VERIFICATION;
        $record->submitted_at = now();
        $record->save();

        return back()->with('status', 'land-submitted-for-review');
    }

    /**
     * Owner requests a correction on a record already in the pipeline.
     */
    public function requestCorrection(Request $request, int $id): RedirectResponse
    {
        $record = $this->ownedRecord($request, $id);

        $this->authorize('requestCorrection', $record);

        $validated = $request->validate([
            'correction_note' => ['required', 'string', 'max:2000'],
        ]);

        $record->status = LandRecord::STATUS_CORRECTION_REQUESTED;
        $record->review_note = $validated['correction_note'];
        $record->save();

        return back()->with('status', 'land-correction-requested');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $record = $this->ownedRecord($request, $id);

        $this->authorize('delete', $record);

        $record->delete();

        return redirect()
            ->route('citizen.land.index')
            ->with('status', 'land-draft-deleted');
    }

    /**
     * The party to attribute, but ONLY when a verified link exists.
     *
     * Returns null when the link is pending or absent, which is what stops a
     * citizen establishing ownership by assertion.
     */
    protected function verifiedPartyId(Request $request): ?int
    {
        return PartyUserLink::query()
            ->where('user_id', $request->user()->id)
            ->where('status', PartyUserLink::STATUS_VERIFIED)
            ->value('party_id');
    }

    /**
     * Apply the draft/submit decision to the record lifecycle.
     */
    protected function applyLifecycle(LandRecord $record, Request $request): void
    {
        $action = $request->input('action', 'draft');

        if ($action === 'submit') {
            $record->status = LandRecord::STATUS_PENDING_VERIFICATION;
            $record->submitted_at = $record->submitted_at ?? now();

            return;
        }

        // Saving a draft must never downgrade a record that is already in review.
        if ($record->exists && ! $record->isEditableByOwner()) {
            return;
        }

        $record->status = LandRecord::STATUS_DRAFT;
    }
}
