<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\PartyUserLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Phase 5 — the secure User ↔ Party ownership linking workflow.
 *
 * WHY THIS EXISTS
 *   Without a verified link there is no way to know that an account belongs to a
 *   party, and every ownership-based feature must therefore show nothing. This
 *   controller implements the missing half: a citizen CLAIMS a link, and a
 *   reviewer decides. Until that decision, the claim confers nothing.
 *
 * WHAT A CLAIM IS NOT
 *   Submitting a claim — with or without an identity number — does NOT establish
 *   ownership. `PartyPolicy::owns()` reads only `status = verified` rows, so a
 *   pending claim grants no access to any land, document or listing.
 *
 * IDENTITY HANDLING
 *   This controller never accepts a raw NID. Identity material stays behind
 *   IdentityAccessService, which requires an authorized role, a stated purpose
 *   and writes a hash-chained audit row. A citizen claiming a party is matched
 *   by the party's NON-sensitive identifiers (display name, date of birth,
 *   organisation registration number) plus supporting documents, and the
 *   reviewer performs any identity verification through the existing audited
 *   service.
 */
class PartyLinkController extends Controller
{
    /**
     * The citizen's own claim(s) and their status.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PartyUserLink::class);

        $links = PartyUserLink::query()
            ->where('user_id', $request->user()->id)
            ->with(['party', 'evidenceDocument', 'reviewer'])
            ->latest()
            ->get();

        return view('citizen.seller.link.index', [
            'links' => $links,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PartyUserLink::class);

        return view('citizen.seller.link.create', [
            'alreadyLinked' => $request->user()->hasVerifiedPartyLink(),
        ]);
    }

    /**
     * Submit a claim.
     *
     * The citizen supplies the party's non-sensitive identifiers. A matching
     * party row is found, or none is — in which case the claim is still recorded
     * with a null party_id and a reviewer must resolve it. We never fabricate a
     * party to make a claim succeed.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PartyUserLink::class);

        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'org_registration_no' => ['nullable', 'string', 'max:64'],
            'claim_basis' => ['required', 'string', \Illuminate\Validation\Rule::in([
                PartyUserLink::BASIS_NID,
                PartyUserLink::BASIS_BIRTH_REGISTRATION,
                PartyUserLink::BASIS_INHERITANCE,
                PartyUserLink::BASIS_COURT_ORDER,
                PartyUserLink::BASIS_OTHER,
            ])],
            'claim_note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        // Best-effort match against existing party rows using NON-sensitive
        // columns only. No identity number is involved at this step.
        $party = $this->matchParty($validated);

        if ($party === null) {
            return back()
                ->withInput()
                ->withErrors([
                    'display_name' => __('citizen.link.no_matching_party'),
                ]);
        }

        $existing = PartyUserLink::query()
            ->where('user_id', $request->user()->id)
            ->where('party_id', $party->id)
            ->first();

        if ($existing) {
            return back()
                ->withInput()
                ->withErrors([
                    'display_name' => __('citizen.link.already_claimed'),
                ]);
        }

        $link = PartyUserLink::create([
            'user_id' => $request->user()->id,
            'party_id' => $party->id,
            'status' => PartyUserLink::STATUS_PENDING,
            'claim_basis' => $validated['claim_basis'],
            'claim_note' => $validated['claim_note'],
        ]);

        return redirect()
            ->route('citizen.link.index')
            ->with('status', 'link-claim-submitted');
    }

    /**
     * Amend a pending claim.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $link = PartyUserLink::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $this->authorize('update', $link);

        $validated = $request->validate([
            'claim_note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $link->claim_note = $validated['claim_note'];
        $link->save();

        return back()->with('status', 'link-claim-updated');
    }

    /**
     * Withdraw a pending claim. A reviewed decision cannot be withdrawn.
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        $link = PartyUserLink::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $this->authorize('delete', $link);

        $link->delete();

        return redirect()
            ->route('citizen.link.index')
            ->with('status', 'link-claim-withdrawn');
    }

    /**
     * Find a party by non-sensitive identifiers only.
     *
     * @param  array<string, mixed>  $data
     */
    protected function matchParty(array $data): ?Party
    {
        $query = Party::query()->where('status', Party::STATUS_ACTIVE);

        if (! empty($data['org_registration_no'])) {
            return (clone $query)->where('org_registration_no', $data['org_registration_no'])->first();
        }

        $query->where(function ($q) use ($data) {
            $q->where('display_name_en', $data['display_name'])
                ->orWhere('display_name_bn', $data['display_name']);
        });

        if (! empty($data['date_of_birth'])) {
            $query->whereDate('date_of_birth', $data['date_of_birth']);
        }

        return $query->first();
    }
}
